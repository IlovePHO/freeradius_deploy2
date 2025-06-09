#! usr/bin/perl -w
use strict;
use Digest::SHA qw(hmac_sha256_hex); 
use Time::Piece;
use Time::Seconds;
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

use constant PROFILE_USERNAME_VERSION => 1;
use constant PROFILE_USERNAME_APPEND_CHAR_LENGTH_0 => 9;
use constant PROFILE_USERNAME_APPEND_CHAR_LENGTH_1 => 3;
use constant PROFILE_USERNAME_APPEND_CHAR_LENGTH_2 => 10;

use constant HMAC_KEY_DIR => '/etc/hmac/';
use constant HMAC_KEY_PREFIX => 'hmac_';
use constant HMAC_KEY_EXT => '.key';

sub parse_embeded_info {
    my $append_chars = shift;

    my %info;
    my $offset = 0;

    $info{'version'}      = substr($append_chars, $offset, 1);
    $offset += 1;

    $info{'device_id'}    = substr($append_chars, $offset, 8);
    $offset += 8;

    $info{'hmac_suffix'}  = substr($append_chars, $offset, 3);
    $offset += 3;

    $info{'expire_year'}  = substr($append_chars, $offset, 3);
    $offset += 3;

    $info{'expire_month'} = substr($append_chars, $offset, 1);
    $offset += 1;

    $info{'expire_day'}   = substr($append_chars, $offset, 2);
    $offset += 2;

    $info{'salt'}         = substr($append_chars, $offset, 3);
    $offset += 3;

    my $expire_date       = sprintf("%d/%d/%d", hex($info{'expire_year'}),
                                    hex($info{'expire_month'}), hex($info{'expire_day'}));
    $info{'expire'}       = Time::Piece->strptime($expire_date, "%Y/%m/%d");

    return \%info;
}

sub get_hmac_key {
    my $hmac_key_suffix = shift;

    my $hmac_key_dir = HMAC_KEY_DIR;
    $hmac_key_dir =~ s/\/$//;
    my $hmac_key_prefix = HMAC_KEY_PREFIX;
    my $hmac_key_ext = HMAC_KEY_EXT;
    if (length($hmac_key_ext) > 0) {
        $hmac_key_ext =~ s/^\.*/./;
    }

    my $hmac_key_path = sprintf("%s/%s%s%s", $hmac_key_dir, $hmac_key_prefix,
                                $hmac_key_suffix, $hmac_key_ext);
    my $hmac_key = undef;
    if (-e $hmac_key_path) {
        open(my $fh, "<", $hmac_key_path);
        while (my $line = <$fh>) {
            $hmac_key = $line;
            last;
        }
        close($fh);
    }

    return $hmac_key;
}

sub calc_hmac_password {
    my $user_name = shift;
    my $hmac_key_suffix = shift;

    my $hmac_key = &get_hmac_key($hmac_key_suffix);
    if (!defined($hmac_key)) {
        return undef;
    }

    my $password = hmac_sha256_hex($user_name, $hmac_key);
    return $password;
}

sub parse_user_name {
    my $user_name = $RAD_REQUEST{'User-Name'};

    my $version = PROFILE_USERNAME_VERSION;
    # Exclude characters for version number and colon.
    my $append_char_length_0 = PROFILE_USERNAME_APPEND_CHAR_LENGTH_0 - 1;
    my $append_char_length_1 = PROFILE_USERNAME_APPEND_CHAR_LENGTH_1;
    my $append_char_length_2 = PROFILE_USERNAME_APPEND_CHAR_LENGTH_2 - 1;

    if ($user_name =~ /^(${version}[0-9a-f]{${append_char_length_0}}[0-9a-zA-Z]{${append_char_length_1}}[0-9a-f]{${append_char_length_2}}):(.+@.+)/) {
        my $append_chars = $1;
        my $original_user_name = $2;
        my $info_ref = &parse_embeded_info($append_chars);

        return {
            'original_user_name' => $original_user_name,
            'profile_user_name'  => $user_name,
            'info'               => $info_ref,
        }; 
    }

    return undef;
}

sub process_modified_user_name {
    my $process_authorize = shift;

    # Skip if the value has already been processed.
    if (exists($RAD_REQUEST{'Ls-Original-User-Name'}) &&
        exists($RAD_REQUEST{'Ls-Profile-User-Name'}) &&
        (!$process_authorize || exists($RAD_CHECK{'Cleartext-Password'}))) {
        return RLM_MODULE_NOOP;
    }

    # Extract the value embedded in User-Name.
    my $parsed_result = &parse_user_name();
    if (!defined($parsed_result)) {
        return RLM_MODULE_NOOP;
    }

    my $info_ref = $parsed_result->{'info'};

    # Save User-Name of DB and Profile as separate Attribute.
    $RAD_REQUEST{'Ls-Original-User-Name'} = $parsed_result->{'original_user_name'};
    $RAD_REQUEST{'Ls-Profile-User-Name'}  = $parsed_result->{'profile_user_name'};
    $RAD_REQUEST{'Ls-Device-Id'}          = $info_ref->{'device_id'};

    if ($process_authorize) {
        my $now = gmtime;
        if ($now > $info_ref->{'expire'} + ONE_DAY) {
            # If the expiration date has expired
            return RLM_MODULE_REJECT;
        }

        my $password = &calc_hmac_password($parsed_result->{'profile_user_name'},
                                           $info_ref->{'hmac_suffix'});
        if (!defined($password)) {
            return RLM_MODULE_FAIL;
        }

        $RAD_CHECK{'Cleartext-Password'} = $password;
        $RAD_CHECK{'Ls-Hmac-Cleartext-Password'} = $password;
        return RLM_MODULE_UPDATED;
    } else {
        return RLM_MODULE_UPDATED;
    }
}

sub authorize {
    return &process_modified_user_name(1);
}

sub accounting {
    return &process_modified_user_name(0);
}

sub post_auth {
    return &process_modified_user_name(0);
}
