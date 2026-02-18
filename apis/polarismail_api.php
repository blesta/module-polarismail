<?php
/**
 * PolarisMail API
 *
 * @package blesta
 * @subpackage blesta.components.modules.polarismail.apis
 * @copyright Copyright (c) 2024, PolarisMail
 */
class PolarisMail_Api
{
    const API_ADMIN_CF_URL = 'https://cfcp.emailarray.com/admin/json.php';
    const API_URL = 'https://cp.emailarray.com/admin/json.php';
    const API_USER_URL = 'https://cp.emailarray.com/json.php';
    const USER_LOGIN_URL = 'https://cp.emailarray.com/processlogin.php';
    const WEBMAIL_LOGIN_URL = 'https://al.emailarray.com/';

    /**
     * @var string The API username
     */
    private $username;

    /**
     * @var string The API password
     */
    private $password;

    /**
     * @var string The cached authentication token
     */
    private $token = null;

    /**
     * @var int The timestamp when the token was cached
     */
    private $token_time = 0;

    /**
     * @var array|null Details for the last request made to the API
     */
    private $last_request = null;

    /**
     * @var string|null The raw response returned from the API
     */
    private $last_response = null;

    /**
     * @var int|null The HTTP status code returned from the API
     */
    private $last_http_code = null;

    /**
     * Initializes the class
     *
     * @param string $username The API username
     * @param string $password The API password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Sends a request to the API
     *
     * @param array $post The POST data
     * @param bool $admin Whether this is an admin API call (true) or user API call (false)
     * @return stdClass The API response
     */
    private function sendRequest($post, $admin = true)
    {
        $post = $this->decode($post);
        $post_data = http_build_query($post);

        $ch = curl_init();

        $url = $admin ? self::API_ADMIN_CF_URL : self::API_USER_URL;
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $this->last_request = [
            'method' => 'POST',
            'url' => $url,
            'data' => $post
        ];

        $response = curl_exec($ch);

        $this->last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($admin && $this->last_http_code >= 500) {
            $url = self::API_URL;
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            $this->last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->last_request = [
                'method' => 'POST',
                'url' => $url,
                'data' => $post
            ];
        }

        curl_close($ch);

        $this->last_response = $response;

        return json_decode($response);
    }

    /**
     * Decodes HTML entities from array/object data
     *
     * @param mixed $data The data to decode
     * @param string|null $key The current key being processed (for password field detection)
     * @return mixed The decoded data
     */
    private function decode($data, $key = null)
    {
        // List of sensitive fields that should not be decoded
        $sensitive_fields = ['password', 'editpassword', 'pass', 'passwd'];

        if (is_array($data)) {
            $result = [];
            foreach ($data as $k => $v) {
                $result[$k] = $this->decode($v, $k);
            }
            return $result;
        }
        if (is_object($data)) {
            $tmp = clone $data;
            foreach ($data as $k => $var) {
                $tmp->{$k} = $this->decode($var, $k);
            }
            return $tmp;
        }

        // Don't decode password fields - they should be literal strings
        if ($key !== null && in_array(strtolower($key), $sensitive_fields, true)) {
            return $data;
        }

        return html_entity_decode($data);
    }

    /**
     * Login to the API
     *
     * @return stdClass The API response containing the token
     */
    public function login()
    {
        // Use cached token if it's less than 5 minutes old
        if ($this->token && (time() - $this->token_time) < 300) {
            return (object) ['returncode' => 1, 'returndata' => $this->token];
        }

        $post = [
            'action' => 'login',
            'username' => $this->username,
            'password' => $this->password
        ];

        $response = $this->sendRequest($post);

        if ($response && isset($response->returndata)) {
            $this->token = $response->returndata;
            $this->token_time = time();
        }

        return $response;
    }

    /**
     * User login to the API
     *
     * @param string $username The username
     * @param string $oPass The one-time password
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function userLogin($username, $oPass, $domain)
    {
        $post = [
            'action' => 'login',
            'username' => $username,
            'password' => $oPass,
            'domain' => $domain
        ];
        return $this->sendRequest($post, false);
    }

    /**
     * Check if a domain is available
     *
     * @param string $token The authentication token
     * @param string $domain The domain to check
     * @return stdClass The API response
     */
    public function isDomainAvailable($token, $domain)
    {
        $post = [
            'action' => 'isDomainAvailable',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Add a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain to add
     * @return stdClass The API response
     */
    public function addDomain($token, $domain)
    {
        $post = [
            'action' => 'addDomain',
            'token' => $token,
            'newdomain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Remove a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain to remove
     * @return stdClass The API response
     */
    public function removeDomain($token, $domain)
    {
        $post = [
            'action' => 'removeDomain',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Disable a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain to disable
     * @return stdClass The API response
     */
    public function disableDomain($token, $domain)
    {
        $post = [
            'action' => 'updateDomain',
            'token' => $token,
            'editdomainactive' => 0,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Enable a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain to enable
     * @return stdClass The API response
     */
    public function enableDomain($token, $domain)
    {
        $post = [
            'action' => 'updateDomain',
            'token' => $token,
            'editdomainactive' => 1,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get domain verification details
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getDomainVerification($token, $domain)
    {
        $post = [
            'action' => 'getDomainVerification',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get domain assigned quota
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getDomainAssignedQuota($token, $domain)
    {
        $post = [
            'action' => 'getDomainAssignedQuota',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get all domains
     *
     * @param string $token The authentication token
     * @return stdClass The API response
     */
    public function getAllDomains($token)
    {
        $post = [
            'action' => 'getAllDomains',
            'token' => $token
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get all users for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @param int $getOTPAndToken Whether to get OTP and token
     * @param bool $pagination Whether to use pagination
     * @param int $offset The offset
     * @param int $limit The limit
     * @param int $showQuota Whether to show quota
     * @return stdClass The API response
     */
    public function getAllUsersDomain($token, $domain, $getOTPAndToken = 0, $pagination = false, $offset = 0, $limit = 10, $showQuota = 0)
    {
        $post = [
            'action' => 'getAllUsersDomain',
            'token' => $token,
            'slicing' => 0,
            'offset' => 0,
            'limit' => 0,
            'getOTPAndToken' => $getOTPAndToken,
            'showQuota' => $showQuota,
            'domain' => $domain
        ];

        if ($pagination) {
            $post['slicing'] = 1;
            $post['offset'] = $offset;
            $post['limit'] = $limit;
        }

        return $this->sendRequest($post);
    }

    /**
     * Get total users for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @param string $atype The account type
     * @return stdClass The API response
     */
    public function getTotalUsersDomain($token, $domain, $atype)
    {
        $post = [
            'action' => 'getTotalUsersDomain',
            'token' => $token,
            'domain' => $domain,
            'atype' => $atype
        ];
        return $this->sendRequest($post);
    }

    /**
     * Search users
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @param string $value The search value
     * @param bool $pagination Whether to use pagination
     * @param int $offset The offset
     * @param int $limit The limit
     * @return stdClass The API response
     */
    public function searchUsers($token, $domain, $value, $pagination = false, $offset = 0, $limit = 10)
    {
        $post = [
            'action' => 'searchUsers',
            'token' => $token,
            'domain' => $domain,
            'search' => $value,
            'slicing' => 0,
            'offset' => 0,
            'limit' => 0
        ];

        if ($pagination) {
            $post['slicing'] = 1;
            $post['offset'] = $offset;
            $post['limit'] = $limit;
        }

        return $this->sendRequest($post);
    }

    /**
     * Add a user
     *
     * @param string $token The authentication token
     * @param array $params The user parameters
     * @param string $lang The language
     * @return stdClass The API response
     */
    public function addUser($token, $params, $lang = 'en')
    {
        $post = [
            'action' => 'addUser',
            'token' => $token,
            'username' => $params['username'],
            'domain' => $params['domain'],
            'account_type' => $params['account_type'],
            'password' => $params['password'],
            'quota' => $params['quota'],
            'uname' => $params['uname'],
            'twofa_allowed' => isset($params['twofa_allowed']) ? $params['twofa_allowed'] : 1,
            'user_language' => $lang
        ];
        return $this->sendRequest($post);
    }

    /**
     * Remove a user
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function removeUser($token, $username, $domain)
    {
        $post = [
            'action' => 'removeUser',
            'token' => $token,
            'username' => $username,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Disable a user
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function disableUser($token, $username, $domain)
    {
        $post = [
            'action' => 'disableUser',
            'token' => $token,
            'username' => $username,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Enable a user
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function enableUser($token, $username, $domain)
    {
        $post = [
            'action' => 'enableUser',
            'token' => $token,
            'username' => $username,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Update a user
     *
     * @param string $token The authentication token
     * @param array $user The user data
     * @return stdClass The API response
     */
    public function updateUser($token, $user)
    {
        $post = $user;
        $post['action'] = 'updateUser';
        $post['token'] = $token;
        return $this->sendRequest($post);
    }

    /**
     * Get user information
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getUserInfo($token, $username, $domain)
    {
        $post = [
            'action' => 'getUserInfo',
            'token' => $token,
            'username' => $username,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get one-time password
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getOtPass($token, $username, $domain)
    {
        $post = [
            'action' => 'getOTPass',
            'token' => $token,
            'username' => $username,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get one-time password and token
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getOtPassAndToken($token, $username, $domain)
    {
        $post = [
            'action' => 'getOTPassAndToken',
            'token' => $token,
            'username' => $username,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get user login URL
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return array An array containing the login URL
     */
    public function getUserLoginUrl($token, $username, $domain)
    {
        $otPass = $this->getOtPass($token, $username, $domain);
        $loginUrl = self::USER_LOGIN_URL . '?u=' . base64_encode($username . '@' . $domain) . '&p=' . base64_encode($otPass->returndata);
        return [$loginUrl];
    }

    /**
     * Get user webmail login URL
     *
     * @param string $token The authentication token
     * @param string $username The username
     * @param string $domain The domain
     * @return array An array containing login details
     */
    public function getUserWebmailLoginUrl($token, $username, $domain)
    {
        $otPass = $this->getOtPassAndToken($token, $username, $domain);
        return ['', $otPass->returndata->otp, $otPass->returndata->token, $otPass->returndata->url];
    }

    /**
     * Add an alias
     *
     * @param string $token The authentication token
     * @param string $alias The alias
     * @param string $domain The domain
     * @param string $forward The forward address
     * @return stdClass The API response
     */
    public function addAlias($token, $alias, $domain, $forward)
    {
        $post = [
            'action' => 'addAlias',
            'token' => $token,
            'alias' => $alias,
            'domain' => $domain,
            'forward' => $forward
        ];
        return $this->sendRequest($post);
    }

    /**
     * Remove an alias
     *
     * @param string $token The authentication token
     * @param string $alias The alias
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function removeAlias($token, $alias, $domain)
    {
        $post = [
            'action' => 'removeAlias',
            'token' => $token,
            'alias' => $alias,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get all aliases for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getAllAliasesDomain($token, $domain)
    {
        $post = [
            'action' => 'getAllAliasesDomain',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Add a distribution list
     *
     * @param string $token The authentication token
     * @param string $lname The list name
     * @param string $domain The domain
     * @param string $ltype The list type
     * @return stdClass The API response
     */
    public function addList($token, $lname, $domain, $ltype)
    {
        $post = [
            'action' => 'addList',
            'token' => $token,
            'newlname' => $lname,
            'domain' => $domain,
            'newlisttype' => $ltype
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get all lists for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getAllListsDomain($token, $domain)
    {
        $post = [
            'action' => 'getAllListsDomain',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Remove a list
     *
     * @param string $token The authentication token
     * @param string $lname The list name
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function removeList($token, $lname, $domain)
    {
        $post = [
            'action' => 'removeList',
            'token' => $token,
            'lname' => $lname,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get list members
     *
     * @param string $token The authentication token
     * @param string $lname The list name
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getListMembers($token, $lname, $domain)
    {
        $post = [
            'action' => 'getListMembers',
            'token' => $token,
            'lname' => $lname,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Add a list member
     *
     * @param string $token The authentication token
     * @param string $lname The list name
     * @param string $domain The domain
     * @param string $email The member email
     * @return stdClass The API response
     */
    public function addListMember($token, $lname, $domain, $email)
    {
        $post = [
            'action' => 'addListMember',
            'token' => $token,
            'editlname' => $lname,
            'editdomain' => $domain,
            'newmember' => $email
        ];
        return $this->sendRequest($post);
    }

    /**
     * Remove a list member
     *
     * @param string $token The authentication token
     * @param string $lname The list name
     * @param string $domain The domain
     * @param string $email The member email
     * @return stdClass The API response
     */
    public function removeListMember($token, $lname, $domain, $email)
    {
        $post = [
            'action' => 'removeListMember',
            'token' => $token,
            'lname' => $lname,
            'domain' => $domain,
            'member' => $email
        ];
        return $this->sendRequest($post);
    }

    /**
     * Add a forward
     *
     * @param string $token The authentication token (user token)
     * @param string $forward The forward email address
     * @return stdClass The API response
     */
    public function addForward($token, $forward)
    {
        $post = [
            'action' => 'addForward',
            'token' => $token,
            'newforward' => $forward
        ];
        return $this->sendRequest($post, false);
    }

    /**
     * Remove a forward
     *
     * @param string $token The authentication token (user token)
     * @param string $forward The forward email address
     * @return stdClass The API response
     */
    public function removeForward($token, $forward)
    {
        $post = [
            'action' => 'removeForward',
            'token' => $token,
            'forward' => $forward
        ];
        return $this->sendRequest($post, false);
    }

    /**
     * Get forwards
     *
     * @param string $token The authentication token
     * @return stdClass The API response
     */
    public function getForwards($token)
    {
        $post = [
            'action' => 'getForwards',
            'token' => $token
        ];
        return $this->sendRequest($post, false);
    }

    /**
     * Get all forwards for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getAllForwardsDomain($token, $domain)
    {
        $post = [
            'action' => 'getAllForwardsDomain',
            'domain' => $domain,
            'token' => $token
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get DKIM information for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getDomainDKIM($token, $domain)
    {
        $post = [
            'action' => 'getDKIM',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Enable DKIM for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function enableDomainDKIM($token, $domain)
    {
        $post = [
            'action' => 'enableDKIM',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Disable DKIM for a domain
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function disableDomainDKIM($token, $domain)
    {
        $post = [
            'action' => 'disableDKIM',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Get domain branding information
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function getDomainBrandInfo($token, $domain)
    {
        $post = [
            'action' => 'getDomainBrandInfo',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Set domain branding
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @param string $brandname The brand name
     * @param string $supportemail The support email
     * @param string $brandcolor The brand color
     * @param string $newlogo The new logo (base64 encoded)
     * @return stdClass The API response
     */
    public function setDomainBranding($token, $domain, $brandname, $supportemail, $brandcolor, $newlogo)
    {
        $post = [
            'action' => 'setDomainBrandingV2',
            'token' => $token,
            'domain' => $domain,
            'brand' => [
                'brandname' => $brandname,
                'supportemail' => $supportemail,
                'brandcolor' => $brandcolor,
                'newlogo' => $newlogo
            ]
        ];
        return $this->sendRequest($post);
    }

    /**
     * Reset domain branding
     *
     * @param string $token The authentication token
     * @param string $domain The domain
     * @return stdClass The API response
     */
    public function resetDomainBranding($token, $domain)
    {
        $post = [
            'action' => 'resetDomainBranding',
            'token' => $token,
            'domain' => $domain
        ];
        return $this->sendRequest($post);
    }

    /**
     * Retrieves details about the last request sent to the API
     *
     * @return array|null An associative array containing method, url, and data
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }

    /**
     * Retrieves the raw response returned from the API
     *
     * @return string|null The raw response body
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * Retrieves the HTTP status code from the last API response
     *
     * @return int|null The HTTP status code
     */
    public function getLastHttpCode()
    {
        return $this->last_http_code;
    }
}
