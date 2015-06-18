<?php
/**
 * Created by: Jon Lawrence on 2015-03-20 2:39 AM
 */

namespace jlawrence\lw;


class Crypt
{

    /**
     * 'Salt' that will be used with passwords to make them more secure.
     */
    private $salt;

    public function __construct($cfgArray)
    {
        $this->salt = $cfgArray['salt'];
    }

    /**
     * Encrypt AES
     *
     * Will Encrypt data with a password in AES compliant encryption.  It
     * adds built in verification of the data so that the {@link LW_crypt::decryptAES}
     * can verify that the decrypted data is correct.
     *
     * @param String $data This can either be string or binary input from a file
     * @param String $pass The Password to use while encrypting the data
     * @return String The encrypted data in concatenated base64 form.
     */
    public function encryptAES($data, $pass) {
        //First, let's change the pass into a 256bit key value so we get 256bit encryption
        $pass = hash('SHA256', $this->salt . $pass, true);
        //Randomness is good since the Initialization Vector(IV) will need it
        srand();
        //Create the IV (CBC mode is the most secure we get)
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
        //Create a base64 version of the IV and remove the padding
        $base64IV = rtrim(base64_encode($iv), '=');
        //Create our integrity check hash
        $dataHash = md5($data);
        //Encrypt the data with AES 128 bit (include the hash at the end of the data for the integrity check later)
        $rawEnc = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $pass, $data . $dataHash, MCRYPT_MODE_CBC, $iv);
        //Transfer the encrypted data from binary form using base64
        $baseEnc = base64_encode($rawEnc);
        //attach the IV to the front of the encrypted data (concatenated IV)
        $ret = $base64IV . $baseEnc;
        return $ret;
    }

    /**
     * Decrypt AES
     *
     * Decrypts data previously encrypted WITH THIS CLASS, and checks the
     * integrity of that data before returning it to the programmer.
     *
     * @param String $data The encrypted data we will work with
     * @param String $pass The password used for decryption
     * @return String|Boolean False if the integrity check doesn't pass, or the raw decrypted data.
     */
    public function decryptAES($data, $pass){
        //We used a 256bit key to encrypt, recreate the key now
        $pass = hash('SHA256', $this->salt . $pass, true);
        //We should have a concatenated data, IV in the front - get it now
        //NOTE the IV base64 should ALWAYS be 22 characters in length.
        $base64IV = substr($data, 0, 22) .'=='; //add padding in case PHP changes at some point to require it
        //change the IV back to binary form
        $iv = base64_decode($base64IV);
        //Remove the IV from the data
        $data = substr($data, 22);
        //now convert the data back to binary form
        $data = base64_decode($data);
        //Now we can decrypt the data
        $decData = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $pass, $data, MCRYPT_MODE_CBC, $iv);
        //Now we trim off the padding at the end that php added
        $decData = rtrim($decData, "\0");
        //Get the md5 hash we stored at the end
        $dataHash = substr($decData, -32);
        //Remove the hash from the data
        $decData = substr($decData, 0, -32);
        //Integrity check, return false if it doesn't pass
        if($dataHash != md5($decData)) {
            return false;
        } else {
            //Passed the integrity check, give use their data
            return $decData;
        }
    }

    /**
     * Generate Password
     *
     * Will create a password 30 chars in length, meant to be used as a one-time
     * password and then used with {@link LW_crypt::encryptAES}.
     *
     * @param $maxLength int Max Length to make the password
     * @return String Password 30 characters in length
     */
    public function generatePassword($maxLength=30) {
        //create a random password here
        //$chars = array( 'a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'i', 'I', 'j', 'J',  'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'o', 'O', 'p', 'P', 'q', 'Q', 'r', 'R', 's', 'S', 't', 'T',  'u', 'U', 'v', 'V', 'w', 'W', 'x', 'X', 'y', 'Y', 'z', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '?', '<', '>', '.', ',', ';', '-', '@', '!', '#', '$', '%', '^', '&', '*', '(', ')');
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789?<>.,;-@!#$%^&*()";
        $maxChars = strlen($chars) - 1;
        srand( (double) microtime()*1000000);

        $randStr = '';
        for($i = 0; $i < $maxLength; $i++)
        {
            $randStr .= $chars[rand(0, $maxChars)];
        }
        return $randStr;

    }

    /**
     * Generate password hash
     *
     * Will use a static salt, plus the 'dynamic' salt inputed from the programer
     * to generate a 'secure' hash of the password.  The dynamic salt, referred to
     * here as 'pepper', should be a per-user salt and not static through the
     * application for all passwords.  An example of pepper to use would be from
     * the user's table and using the 'registration time' that the user signed up
     * on as that will change for every user.  NOTE this function is SUPPOSE TO BE
     * SLOW.  It will hash the password a few thousand times for 'stretching' the
     * hash.
     *
     * @param $password String the plain-text password to hash
     * @param $pepper String The dynamic salt to be used
     * @return String the hash to use for storage/comparison
     */
    public function passHash($password, $pepper) {
        $salt = $this->salt;
        $hash = hash('SHA512', $salt . $password . $pepper);
        for($i=0;$i<100000; $i++) {
            if(($i%3)==0) {
                $hash = hash('SHA512', $salt . $hash . $pepper);
            } elseif (($i%3)==1) {
                $hash = hash('SHA512', $pepper . $hash . $salt);
            } elseif (($i%3)==2) {
                $hash = hash('SHA512', $hash . $pepper . $salt);
            }
        }
        return $hash;
    }

    /**
     * Generate Key Pair
     *
     * This function will use OpenSSL to generate a public/private
     * key-pair that is then returned to be stored how the programmer
     * sees fit.  Since this class isn't meant to make certificates
     * or anything of the sort, none are made, just a private key,
     * and a public key ^^
     *
     * @return Array Returns an associative array 'privateKey' and 'publicKey'
     */
    public function makeKeyPair()
    {
        //Define variables that will be used, set to ''
        $private = '';
        //$public = '';
        //Generate the resource for the keys
        $resource = openssl_pkey_new();

        //get the private key
        openssl_pkey_export($resource, $private);

        //get the public key
        $public = openssl_pkey_get_details($resource);
        $public = $public["key"];
        $ret = array('privateKey' => $private, 'publicKey' => $public);
        return $ret;
    }

    /**
     * Public Encryption
     *
     * Will encrypt data based on the public key
     *
     * @param String $data The data to encrypt
     * @param String $publicKey The public key to use
     * @return String The Encrypted data in base64 coding
     */
    public function publicEncrypt($data, $publicKey) {
        //Set up the variable to get the encrypted data
        $encData = '';
        openssl_public_encrypt($data, $encData, $publicKey);
        //base64 code the encrypted data
        $encData = base64_encode($encData);
        //return it
        return $encData;
    }

    /**
     * Private Decryption
     *
     * Decrypt data that was encrypted with the assigned private
     * key's public key match. (You can't decrypt something with
     * a private key if it doesn't match the public key used.)
     *
     * @param String $data The data to decrypt (in base64 format)
     * @param String $privateKey The private key to decrypt with.
     * @return String The raw decoded data
     */
    public function privateDecrypt($data, $privateKey) {
        //Set up the variable to catch the decoded date
        $decData = '';
        //Remove the base64 encoding on the inputted data
        $data = base64_decode($data);
        //decrypt it
        openssl_private_decrypt($data, $decData, $privateKey);
        //return the decrypted data
        return $decData;
    }

    /**
     * Secure Send
     *
     * OpenSSL and 'public-key' schemes are good for sending
     * encrypted messages to someone that can then use their
     * private key to decrypt it.  However, for large amounts
     * of data, this method is incredibly slow (and limited).
     * This function will take the public key to encrypt the data
     * to, and using that key will encrypt a one-time-use randomly
     * generated password.  That one-time password will be
     * used to encrypt the data that is provided.  So the data
     * will be encrypted with a one-time password that only
     * the owner of the private key will be able to uncover.
     * This method will return a base64encoded serialized array
     * so that it can easily be stored, and all parts are there
     * without modification for the receive function
     *
     * @param String $data The data to encrypt
     * @param String $publicKey The public key to use
     * @return String serialized array of 'password' and 'data'
     */
    public function secureSend($data, $publicKey)
    {
        //First, we'll create a 30digit random password
        $pass = $this->generatePassword(30);
        //Now, we will encrypt in AES the data
        $encData = $this->encryptAES($data, $pass);
        //Now we will encrypt the password with the public key
        $pass = $this->publicEncrypt($pass, $publicKey);
        //set up the return array
        $ret = array('password' => $pass, 'data' => $encData);
        //serialize the array and then base64 encode it
        $ret = serialize($ret);
        $ret = base64_encode($ret);
        //send it on its way
        return $ret;
    }

    /**
     * Secure Receive
     *
     * This is the complement of {@link this::secureSend}.
     * Pass the data that was returned from secureSend, and it
     * will dismantle it, and then decrypt it based on the
     * private key provided.
     *
     * @param String $data the base64 serialized array
     * @param String $privateKey The private key to use
     * @return String the decoded data.
     */
    public function secureReceive($data, $privateKey) {
        //Let's decode the base64 data
        $data = base64_decode($data);
        //Now let's put it into array format
        $data = unserialize($data);
        //assign variables for the different parts
        $pass = $data['password'];
        $data = $data['data'];
        //Now we'll get the AES password by decrypting via OpenSSL
        $pass = $this->privateDecrypt($pass, $privateKey);
        //and now decrypt the data with the password we found
        $data = $this->decryptAES($data, $pass);
        //return the data
        return $data;
    }
}