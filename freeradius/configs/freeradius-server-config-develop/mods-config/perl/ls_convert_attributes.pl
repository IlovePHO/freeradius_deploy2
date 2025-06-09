#! usr/bin/perl -w
use strict;
use FindBin;
use DBI;
use Cwd 'getcwd';
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

sub get_nas_type {
    if (defined($RAD_REQUEST{"NAS-Identifier"})) {
        my @res = $dbh->selectrow_array('SELECT nas.type FROM nas WHERE nasidentifier=?', {},
                      ($RAD_REQUEST{"NAS-Identifier"}));
        if ($#res >= 0) {
            return $res[0];
        }
    }
    return undef;
}

sub get_attribute_convert_table {
    my $nas_type = shift;

    # NOTE: It might be better to create a cache instead of executing SQL every time.
    my %convert_table;
    if (defined($nas_type)) {
        my $res = $dbh->selectall_hashref('SELECT src, dst FROM attribute_converts WHERE nas_type=?',
                      'src', undef, $nas_type);
        foreach my $key (keys(%{$res})) {
            $convert_table{$key} = $res->{$key}->{'dst'};
        }
        return \%convert_table;
    }
    return undef;
}

sub convert_attributes_internal {
    if (!defined($dbh)) {
        &connect_mysql;
    }
    if (!defined($dbh)) {
        return RLM_MODULE_NOOP;
    }

    my $nas_type = &get_nas_type;
    if (!defined($nas_type)) {
        return RLM_MODULE_NOOP;
    }

    my $convert_table_ref = &get_attribute_convert_table($nas_type);
    if (defined($convert_table_ref)) {
        for (keys %RAD_REPLY) {
            my $key = $_;
            if (exists($convert_table_ref->{$key})) {
                $RAD_REPLY{$convert_table_ref->{$key}} = $RAD_REPLY{$key};
                delete($RAD_REPLY{$key});
                &radiusd::radlog(L_DBG, "reply convert: $key -> $convert_table_ref->{$key}");
            }
        }
        return RLM_MODULE_UPDATED;
    } else {
        return RLM_MODULE_NOOP;
    }
}

sub convert_attributes {
    my $ret = RLM_MODULE_NOOP;
    for (my $i = 0; $i < &DB_RETRY_COUNT + 1; $i++) {
        eval {
            $ret = &convert_attributes_internal;
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
    return &convert_attributes;
}
