#! usr/bin/perl -w
use strict;
use Crypt::CBC;
use MIME::Base64;
use Data::Dumper;

# use ...
# This is very important!
use vars qw(%RAD_REQUEST %RAD_CHECK %RAD_REPLY %RAD_CONFIG);
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

sub decrypt_attribute {
    my $attribute_key = shift; 
    if (!exists($RAD_CHECK{$attribute_key}) ||
        $attribute_key !~ /^Ls-AES-/) {
        return RLM_MODULE_NOOP;
    }

    if (!exists($ENV{'AES_KEY'})) {
        &radiusd::radlog(L_ERR, "The AES_KEY environment variable is not set.");
        return RLM_MODULE_FAIL;
    }

    #my $key = '0123456789ABCDEF0123456789ABCDEF';
    my $key = $ENV{'AES_KEY'};
    
    my $iv;
    my $base64_encrypted;

    my $encrypted_with_iv = $RAD_CHECK{$attribute_key};
    if ($encrypted_with_iv =~ /^([^:]{16}):([^:]+)$/) {
        $iv = $1;
        $base64_encrypted = $2;
    } else {
        &radiusd::radlog(L_ERR, "There is a problem with the value of the ".
                         $attribute_key." attribute.");
        return RLM_MODULE_FAIL;
    }

    my $cipher = Crypt::CBC->new(
        -key         => $key,
        -keysize     => length($key),
        -cipher      => "Crypt::Rijndael",
        -iv          => $iv,
        -header      => 'none',
        #-literal_key => 1, # Comment out if key is not md5 hashed internally.
    );

    my $aes_encrypted = MIME::Base64::decode_base64($base64_encrypted);
    my $decrypted = $cipher->decrypt($aes_encrypted);

    my $decrypted_attribute_key = $attribute_key;
    $decrypted_attribute_key =~ s/^Ls-AES-//;
    $RAD_CHECK{$decrypted_attribute_key} = $decrypted;
    $RAD_CONFIG{$decrypted_attribute_key} = $decrypted;

    if (exists($RAD_CHECK{$attribute_key})) {
        delete($RAD_CHECK{$attribute_key});
    }
    if (exists($RAD_CONFIG{$attribute_key})) {
        delete($RAD_CONFIG{$attribute_key});
    }

    return RLM_MODULE_UPDATED;
}

sub decrypt_attributes {
    # If processing in ls_calc_hmac_password.pl is done,
    # processing in this module is skipped.
    if (exists($RAD_REQUEST{'Ls-Original-User-Name'}) &&
        exists($RAD_REQUEST{'Ls-Profile-User-Name'})) {
        return RLM_MODULE_NOOP;
    }

    my $result = RLM_MODULE_NOOP;
    foreach my $attribute_key (keys(%RAD_CHECK)) {
        if ($attribute_key !~ /^Ls-AES-/) {
            next;
        }

        my $ret = &decrypt_attribute($attribute_key);
        if ($ret != RLM_MODULE_NOOP && $result != $ret) {
            $result = $ret;
        }
    }

    return $result;
}

sub authorize {
    return &decrypt_attributes;
}
