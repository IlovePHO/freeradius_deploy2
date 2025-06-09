#!/usr/bin/perl -w
use strict;
use DBI;
use File::Basename qw(dirname);
use File::Temp qw(tempfile);
use File::Copy qw(move);
use File::Path;
use Digest::MD5 qw(md5_hex);
use Getopt::Long 'GetOptions';
use Data::Dumper;

use constant FREERADIUS_USER => 'freerad';
use constant FREERADIUS_GROUP => 'freerad';

use constant SQL_CONF_PATH =>
    "/etc/freeradius/mods-available/sql";

use constant LS_REALM_CONF_PATH =>
    "/etc/freeradius/proxy.d/ls_realm.conf";

use constant LS_CLIENTS_CONF_PATH =>
    "/etc/freeradius/clients.d/ls_clients.conf";

my $dbh;

sub read_sql_conf {
    my @context;
    my %sql_conf;

    open(SQL_CONF, "<", &SQL_CONF_PATH) or die $!;

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
        }
    }

    close(SQL_CONF);
    return \%sql_conf;
}

sub connect_mysql {
    my $sql_conf_ref = &read_sql_conf;

    my $user = $sql_conf_ref->{'login'};
    my $passwd = $sql_conf_ref->{'password'};
    my $host = $sql_conf_ref->{'server'};
    my $database = $sql_conf_ref->{'radius_db'};

    $dbh = DBI->connect("DBI:mysql:database=$database;host=$host", $user, $passwd, {PrintError => 0});
    if (!defined($dbh)) {
        die("database connect error");
    }
}

sub calc_realm_update_hash {
    # Obtain a list of table names and updated dates and times related to proxy settings,
    # concatenate them as strings, and then hash them.

    my $check_update_sql =
        'SELECT TABLE_NAME, UPDATE_TIME FROM information_schema.tables WHERE table_schema = DATABASE() AND
         TABLE_NAME IN ("proxies", "home_server_pools", "home_servers")';
    my $table_updates = $dbh->selectall_arrayref($check_update_sql, +{Slice => {}});
    my $status_raw_str = '';
    foreach my $table_update (@{$table_updates}) {
        if (defined($table_update->{'UPDATE_TIME'})) {
            $status_raw_str .= sprintf("%s,%s,", $table_update->{'TABLE_NAME'}, $table_update->{'UPDATE_TIME'});
        }
    }
    return md5_hex($status_raw_str);
}

sub get_update_hash_in_conf {
    # Read the hash value output in the first line of the configuration file.

    my $path = shift;

    open(REALM_CONF, "<", $path) or return "";
    my $line = <REALM_CONF>;
    close(REALM_CONF);

    if ($line =~ /^\# ([0-9a-f]{32})$/) {
        return $1;
    }
    return '';
}

sub generate_home_server_conf {
    my $home_server = shift;
    my $indent = " " x 4;

    my $conf = sprintf("home_server %s {\n", $home_server->{'name'});
    if (exists($home_server->{'description'}) && defined($home_server->{'description'})) {
        $conf .= sprintf("%s# %s\n", $indent, $home_server->{'description'});
    }

    foreach my $key ('type', 'ipaddr', 'secret', 'port', 'status_check') {
        if (!exists($home_server->{$key})) {
            # error
            return '';
        }
        $conf .= sprintf("%s%s = %s\n", $indent, $key, $home_server->{$key});
    }
    
    $conf .= "}\n";

    #print $conf;
    return $conf;
}

sub generate_home_server_pool_conf {
    my $home_server_pool = shift;
    my $home_servers = shift;
    my $indent = " " x 4;

    my $conf = sprintf("home_server_pool %s {\n", $home_server_pool->{'name'});
    if (exists($home_server_pool->{'description'}) && defined($home_server_pool->{'description'})) {
        $conf .= sprintf("%s# %s\n", $indent, $home_server_pool->{'description'});
    }

    foreach my $key ('type') {
        if (!exists($home_server_pool->{$key})) {
            # error
            return '';
        }
        $conf .= sprintf("%s%s = %s\n", $indent, $key, $home_server_pool->{$key});
    }

    foreach my $home_server (@{$home_servers}) {
        if (!exists($home_server->{'name'})) {
            # error
            return '';
        }
        $conf .= sprintf("%s%s = %s\n", $indent, 'home_server', $home_server->{'name'});
    }

    $conf .= "}\n";

    #print $conf;
    return $conf;
}

sub generate_realm_conf {
    my $realm = shift;
    my $home_server_pool = shift;
    my $indent = " " x 4;

    my $conf = sprintf("realm %s {\n", $realm->{'name'});
    $conf .= sprintf("%s%s = %s\n", $indent, 'pool', $home_server_pool->{'name'});
    $conf .= sprintf("%s%s\n", $indent, 'nostrip');
    $conf .= "}\n";

    #print $conf;
    return $conf;
}

sub generate_client_conf {
    my $home_server = shift;
    my $indent = " " x 4;

    my $conf = sprintf("client %s {\n", $home_server->{'name'});
    if (exists($home_server->{'description'}) && defined($home_server->{'description'})) {
        $conf .= sprintf("%s# %s\n", $indent, $home_server->{'description'});
    }

    foreach my $key ('ipaddr', 'secret') {
        if (!exists($home_server->{$key})) {
            # error
            return '';
        }
        $conf .= sprintf("%s%s = %s\n", $indent, $key, $home_server->{$key});
    }

    $conf .= "}\n";

    #print $conf;
    return $conf;
}

sub prepare_conf_dir {
    my $path = shift;

    my $dir = dirname($path);
    if (length($dir) == 0) {
        return;
    }

    if (! -d $dir) {
        mkpath($dir);

        my $uid;
        my $gid;
        defined ($uid = getpwnam &FREERADIUS_USER) or die 'bad user';
        defined ($gid = getgrnam &FREERADIUS_GROUP) or die 'bad group';
        chown $uid, $gid, $dir;
    }
}

sub write_conf_file {
    my $path = shift;
    my $conf = shift;

    my ($tempfh, $tempfile) = tempfile;
    print $tempfh $conf;
    close($tempfh);

    chmod 0664, $tempfile;

    my $uid;
    my $gid;
    defined ($uid = getpwnam &FREERADIUS_USER) or die 'bad user';
    defined ($gid = getgrnam &FREERADIUS_GROUP) or die 'bad group';
    chown $uid, $gid, $tempfile;

    move $tempfile, $path or die "Can't move \"$tempfile\" to \"$path\": $!";
}

sub restart_freeradius {
    system("systemctl restart freeradius");
}

sub generate_ls_realm_conf {
    my $calculated_hash = shift;

    my $list_proxy_sql = 'SELECT * FROM proxies';
    my $home_server_pools_sql = 'SELECT * from home_server_pools WHERE id = ?';
    my $home_servers_sql = 'SELECT * FROM home_servers WHERE home_server_pool_id = ? ORDER BY priority';

    my $conf = sprintf("# %s\n\n", $calculated_hash);

    my $proxies = $dbh->selectall_arrayref($list_proxy_sql, +{Slice => {}});
    foreach my $proxy (@{$proxies}) {
        my $home_server_pools = $dbh->selectall_arrayref($home_server_pools_sql,
                                                         +{Slice => {}}, $proxy->{'home_server_pool_id'});
        foreach my $home_server_pool (@{$home_server_pools}) {
            my $home_servers = $dbh->selectall_arrayref($home_servers_sql, +{Slice => {}},
                                                        $home_server_pool->{'id'});
            foreach my $home_server (@{$home_servers}) {
                $conf .= &generate_home_server_conf($home_server);
            }
            $conf .= &generate_home_server_pool_conf($home_server_pool, $home_servers);
            $conf .= &generate_realm_conf($proxy, $home_server_pool);
        }
    }

    return $conf;
}

sub generate_ls_clients_conf {
    my $calculated_hash = shift;

    my $home_servers_sql = 'SELECT * FROM home_servers';

    my $conf = sprintf("# %s\n\n", $calculated_hash);

    my $home_servers = $dbh->selectall_arrayref($home_servers_sql, +{Slice => {}});
    foreach my $home_server (@{$home_servers}) {
        $conf .= &generate_client_conf($home_server);
    }

    return $conf;
}

sub main {
    # If the executing user ID is not root, the program terminates.
    if ($> != 0) {
        print "Run with root privileges.\n";
		exit 1
    }

    # Parses command options.
    my $restart_daemon = 0;
    GetOptions(
        'restart_daemon' => \$restart_daemon,
    );

    # Connect to the database.
    &connect_mysql;
    if (!defined($dbh)) {
        exit 1;
    }

    # Check the update status of proxy settings in the DB.
    my $pre_calculated_hash = &calc_realm_update_hash;
    my $realm_conf_hash = &get_update_hash_in_conf(&LS_REALM_CONF_PATH);
    my $clients_conf_hash = &get_update_hash_in_conf(&LS_CLIENTS_CONF_PATH);
    if ($pre_calculated_hash eq $realm_conf_hash &&
        $pre_calculated_hash eq $clients_conf_hash) {
        # There is no change in proxy settings.
        exit 0;
    }

    for (my $i = 0; $i < 5; $i++) {
        # Generate a configuration file for proxy.
        my $realm_conf = &generate_ls_realm_conf($pre_calculated_hash);
        my $clients_conf = &generate_ls_clients_conf($pre_calculated_hash);

        # Recalculate the database hash after generating the settings.
        my $post_calculated_hash = &calc_realm_update_hash;
        if ($post_calculated_hash eq $pre_calculated_hash) {
            # Write the configuration file to a file.
            &prepare_conf_dir(&LS_REALM_CONF_PATH);
            &prepare_conf_dir(&LS_CLIENTS_CONF_PATH);

            &write_conf_file(&LS_REALM_CONF_PATH, $realm_conf);
            &write_conf_file(&LS_CLIENTS_CONF_PATH, $clients_conf);

            # Restart freeradius.
            if ($restart_daemon) {
                &restart_freeradius;
            }

            last;
        } else {
            # If the hash has changed, regenerate the configuration.
            $pre_calculated_hash = $post_calculated_hash;
            sleep(1);
        }
    }
}

&main;
