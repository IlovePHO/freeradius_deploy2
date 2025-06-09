#! usr/bin/perl -w
use strict;
use FindBin;
use DBI;
use Cwd 'getcwd';
use Digest::MD5 qw(md5_hex);
use Time::HiRes 'sleep';
use Data::Dumper;

# use ...
# This is very important!
use vars qw(%RAD_REQUEST %RAD_CHECK %RAD_REPLY);
use constant RLM_MODULE_FAIL=> 1;# /* the module is failed */
use constant RLM_MODULE_OK=> 2;# /* the module is OK,continue */
use constant RLM_MODULE_UPDATED=> 8;# /* OK (pairs modified) */
use constant RLM_MODULE_REJECT=> 0;# /* immediately reject therequest */
use constant RLM_MODULE_NOOP=> 7;

# Same as src/include/radiusd.h
use constant	L_DBG=>   1;
use constant	L_AUTH=>  2;
use constant	L_INFO=>  3;
use constant	L_ERR=>   4;
use constant	L_PROXY=> 5;
use constant	L_ACCT=>  6;

use constant DB_RETRY_COUNT => 1;

my $dbh;
my $table_hash;
my %ssid_regex_list;

sub read_sql_conf {
    my $script_dir = $FindBin::Bin;
    open(SQL_CONF, "< $script_dir/../../mods-available/sql") or die;
    my @context;
    my %sql_conf;

    while (my $line = <SQL_CONF>){
        $line =~ s/^[\s]*(.*?) *$/$1/;
        if ($line =~ /^#/) {
            next;
        }
        if ($line =~ /^([\w]+)[\s]*\{/) {
            push(@context, $1);
        }
        if ($line =~ /^\}/) {
            pop(@context);
        }
        if ($#context < 0 || $context[$#context] ne "sql" || length($line) == 0) {
            next;
        }
        if ($line =~ /^([\w]+)[\s]*=[\s'"]*([\w\.\-]+)/) {
            $sql_conf{$1} = $2;
        } else {
            #&radiusd::radlog(L_DBG, $line);
        }
    }

    #&radiusd::radlog(L_DBG, Dumper(\%sql_conf));

    close(SQL_CONF);
    return \%sql_conf;
}

sub connect_mysql {
    my $sql_conf_ref = &read_sql_conf;

    my $user = $sql_conf_ref->{'login'};
    my $passwd = $sql_conf_ref->{'password'};
    my $host = $sql_conf_ref->{'server'};
    my $database = $sql_conf_ref->{'radius_db'};

    my $port = $sql_conf_ref->{'port'};
    if (!defined($port)) {
        $port = 3306;
    }

    $dbh = DBI->connect("DBI:mysql:database=$database;host=$host;port=$port",
                        $user, $passwd, {
                            mysql_enable_utf8   => 1,
                            RaiseError          => 1,
                            PrintError          => 1,
                            ShowErrorStatement  => 1,
                            AutoInactiveDestroy => 1,
                        });
    if (!defined($dbh)) {
        &radiusd::radlog(L_ERR, "database connect error");
    } else {
        &radiusd::radlog(L_INFO, "database connect success");
    }
}

sub close_mysql {
    if (!defined($dbh)) {
        return;
    }

    $dbh->disconnect;
    $dbh = undef;
}

sub calc_table_update_hash {
    # Obtain a list of table names and updated dates and times related to proxy settings,
    # concatenate them as strings, and then hash them.

    my $check_update_sql =
        'SELECT TABLE_NAME, UPDATE_TIME FROM information_schema.tables '.
        'WHERE table_schema = DATABASE() AND TABLE_NAME IN '.
        '("proxy_decision_conditions")';
    my $table_updates = $dbh->selectall_arrayref($check_update_sql, +{Slice => {}});
    my $status_raw_str = '';
    foreach my $table_update (@{$table_updates}) {
        if (defined($table_update->{'UPDATE_TIME'})) {
            $status_raw_str .= sprintf("%s,%s,",
                $table_update->{'TABLE_NAME'}, $table_update->{'UPDATE_TIME'});
        }
    }
    return md5_hex($status_raw_str);
}

sub load_proxy_decision_conditions_ssid_regex {
    my %tmp_regex_list;

    my $sql = 'SELECT id,ssid FROM proxy_decision_conditions';
    my $conditions = $dbh->selectall_arrayref($sql, +{Slice => {}});
    foreach my $condition (@{$conditions}) {
        my $id   = $condition->{'id'};
        my $ssid = $condition->{'ssid'};
        if (!exists($tmp_regex_list{$ssid})) {
            $tmp_regex_list{$ssid} = [];
        }
        push(@{$tmp_regex_list{$ssid}}, $id);
    }

    %ssid_regex_list = %tmp_regex_list;
}

sub get_decision_conditions {
    my $ssid = shift;
    my $user_name = shift;

    my @id_list;
    foreach my $ssid_regex (keys(%ssid_regex_list)) {
        if ($ssid =~ $ssid_regex) {
            push(@id_list, @{$ssid_regex_list{$ssid_regex}});
        }
    }

    my $id_list_num = @id_list;
    if ($id_list_num <= 0) {
        return undef;
    }

    my $place_holder = '?,' x $id_list_num;
    $place_holder = substr($place_holder, 0, -1);

    my $sql = sprintf('SELECT proxy_decision_conditions.user_name_regex AS user_name_regex,
                       proxies.name AS proxy_name FROM proxy_decision_conditions
                       INNER JOIN proxies ON proxy_decision_conditions.proxy_id = proxies.id
                       WHERE proxy_decision_conditions.id IN (%s)
                       ORDER BY proxy_decision_conditions.priority DESC', $place_holder);

    my $conditions = $dbh->selectall_arrayref($sql, +{Slice => {}}, @id_list);
    foreach my $condition (@{$conditions}) {
        if (defined($condition->{'user_name_regex'})) {
            if ($user_name =~ /$condition->{'user_name_regex'}/) {
                return $condition->{'proxy_name'};
            }
        } else {
            return $condition->{'proxy_name'};
        }
    }
    return undef;
}

sub determine_proxy_destination_internal {
    if (!defined($dbh)) {
        &connect_mysql;
    }
    if (!defined($dbh)) {
        return RLM_MODULE_NOOP;
    }

    # Calculate hash value from table name and update time.
    my $current_hash = &calc_table_update_hash;
    if (!defined($table_hash) || $table_hash ne $current_hash) {
        # Retrieve the proxy_decision_conditions table if it has not been retrieved or updated.
        &load_proxy_decision_conditions_ssid_regex;
        $table_hash = $current_hash;
    }

    if (defined($RAD_REQUEST{"Called-Station-Id"})) {
        if ($RAD_REQUEST{"Called-Station-Id"} =~ /:([^:]+)$/) {
            my $ssid = $1;
            my $proxy_name = &get_decision_conditions($ssid, $RAD_REQUEST{'User-Name'});
            if (defined($proxy_name)) {
                $RAD_CHECK{'Ls-Proxy-Check-Request'} = $proxy_name;
                return RLM_MODULE_UPDATED;
            }
        }
        return RLM_MODULE_NOOP;
    } else {
        return RLM_MODULE_NOOP;
    }
}

sub determine_proxy_destination {
    my $ret = RLM_MODULE_NOOP;
    for (my $i = 0; $i < &DB_RETRY_COUNT + 1; $i++) {
        eval {
            $ret = &determine_proxy_destination_internal;
        };
        if ($@) {
            &radiusd::radlog(L_ERR, $@);
            &close_mysql;
            sleep 0.1;
            $ret = RLM_MODULE_FAIL;
            next;
        } else {
            last;
        }
    }
    return $ret;
}

sub authorize {
    return &determine_proxy_destination;
}
