<?php
/**
 * WindowsNotification Namespace
 * Require PHP v>=5.3 
 * @version 1.0
 * @author Andrea Vincenzo Abbondanza
 */
namespace WindowsNotification
{
    /**
     * Send push notification in PHP for windows 
     *
     * This PHP class provide methods to use push notification for windows Platform 
     *
     */
    class WindowsNotificationClass
    {
        /**
         * Package security identifier for universal windows app
         * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465407.aspx
         * @var string
         */
        private static $SID = "";
        
        /**
         * Secret token for WNS OAuth authentication
         * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465407.aspx
         * @var string
         */
        private static $ClientSecret = "";
        
        /**
         * Link for WNS authentication
         * @var string
         */
        private static $AuthUrl = "https://login.live.com/accesstoken.srf";
        
        /**
         * The options configuration for the WNS
         * @var WNSNotificationOptions
         */
        private $Options = null;
        
        /**
         * Set the header settings for notification request
         * @param WNSNotificationOptions $options The token
         * @throws  \InvalidArgumentException if the provided argoument isn't of type WNSNotificationOptions
         */
        public function SetOptions($options)
        {
            if($options instanceof WNSNotificationOptions)
                $this->Options = $options;
            else
                throw new \InvalidArgumentException("Header settings must be a NotificatoinOptions object");
        }
        /**
         * Build from the body content string for the autentication
         * @return string body content for authentication request
         */
        private function BuildRequest()
        {
            $encodedSID = urlencode(WindowsNotificationClass::$SID);
            $encodedSecret = urlencode(WindowsNotificationClass::$ClientSecret);
            $body = "grant_type=client_credentials&client_id=$encodedSID&client_secret=$encodedSecret&scope=notify.windows.com";
            return $body;
        }
        
        /**
         * Authenticate service
         * Provide to authenticate the application.
         * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-23#section-5.2 
         * @return mixed array with authentication status (200 OK, 400 Error: see OAuth for details), token access and type
         */
        public function AuthenticateService()
        {
            $request = curl_init(WindowsNotificationClass::$AuthUrl);
            $body = $this->BuildRequest();
            curl_setopt($request,CURLOPT_HTTPHEADER,
                                 array("Content-Type : application/x-www-form-urlencoded"));
            curl_setopt($request,CURLOPT_POST,1);
            curl_setopt($request,CURLOPT_POSTFIELDS,$body);
            curl_setopt($request,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($request);
            $response = json_decode($response);
            $response->token_type =  ucfirst($response->token_type);
            $response->response_status = curl_getinfo($request,CURLINFO_HTTP_CODE);
            return $response;
        }
        
        /**
         * Constructor. Provide to set the token if passed
         * @param WNSNotificationOptions $options OPTIONAL: Header settings
         */
        public final function __construct($options = null)
        {
            if($options != null)
                $this->SetOptions($options);
        }
        

        /**
         * Send a toast notification
         * @param string $channel The URI Channel for WNS
         * @param string $toastTemplate Toast xml template with values
         * @return array Return array with "WNS"=> all private WNS header and response => response code
         */
        public function Send($channel, $template, $method = HTTPMethod::Post)
        {
            $Header = $this->Options->GetHeaderArray();
            $request = curl_init($channel);
            if($method == HTTPMethod::Delete)
                $Header["ContentLength"] = "Content-Length : 0";
            else
                curl_setopt($request,CURLOPT_POSTFIELDS, $template);
            curl_setopt($request,CURLOPT_HTTPHEADER, $Header);
            curl_setopt($request,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($request, CURLOPT_HEADER, 1);
            curl_setopt($request,CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($request, CURLINFO_HEADER_OUT, 1);
            $result = curl_exec($request);
            $result = array( "WNS" =>  explode("\n",$result),"response" => curl_getinfo($request,CURLINFO_HTTP_CODE));
            return $result;
        }
    }
    
    /**
     * This class represent the headers options. 
     * For details:
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx
     */
    class WNSNotificationOptions
    {
        #region attributes
        /**
         * Current token. It can be set by SetToken method. A token is valid for 24h 
         * @var OAuthObject
         * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#requesting_and_receiving_an_access_token
         */
        private $Authorization = null;        
        /**
         * Notification type
         * @var string
         */
        private $X_WNS_TYPE = X_WNS_Type::__default;
        /**
         * Cache policy
         * @var string
         */
        private $X_WNS_CACHE_POLICY = X_WNS_Cache_Policy::__default;
        /**
         * The request status flag
         * @var string
         */
        private $X_WNS_REQUESTFORSTATUS = X_WNS_RequestForStatus::__default;
        /**
         * Suppress popup flag
         * @var string
         */
        private $X_WNS_SUPRESSPOPUP = X_WNS_SuppressPopup::__default;        								
        /**
         * The content type
         * @var string
         */
        private $ContentType = Content_Type::__default;

        /**
         * The notification Tag for notification queue (only for tile)
         * @var string
         */
        private $X_WNS_TAG = null;
        /**
         * TTL duration
         * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_ttl
         * @var integer
         */
        private $X_WNS_TTL = null;
        /**
         * Either TAG this regroup notification in operation center
         * @var string
         */
        private $X_WNS_GROUP = null;
        /**
         * For notification delete
         * @var string
         */
        private $X_WNS_MATCH = null;
        #endregion
        /**
         * Set the token for push requests
         * @param OAuthObject $token The token
         * @throws  \InvalidArgumentException if the provided argoument isn't of type string
         */
        public function SetAuthorization($token)
        {
            if($token instanceof OAuthObject)
                $this->Authorization = $token;
            else
                throw new \InvalidArgumentException("The token must be a type OAuthObject");
        }
        /**
         * Set the tile type
         * @param string $type 
         * @throws  \InvalidArgumentException if the provided type isn't of type X_WNS_Type
         */
        public function SetX_WNS_TYPE($type)
        {
            if($this->IsValidParam($type,"X_WNS_Type"))
                $this->X_WNS_TYPE = $type;
            else
                throw new \InvalidArgumentException("The type must be a X_WNS_Type value");
        }
        /**
         * Set the cache policy
         * @param string $cp
         * @throws  \InvalidArgumentException if the provided type isn't of type X_WNS_Cache_Policy
         */
        public function SetX_WNS_CACHE_POLICY($cp)
        {
            if($this->IsValidParam($cp,"X_WNS_Cache_Policy"))
                $this->X_WNS_CACHE_POLICY = $cp;
            else
                throw new \InvalidArgumentException("The type must be a X_WNS_Cache_Policy value");
        }
        /**
         * Set the request for status header
         * @param string $request
         * @throws  \InvalidArgumentException if the provided type isn't of type X_WNS_RequestForStatus
         */
        public function SetX_WNS_REQUESTFORSTATUS($request)
        {
            if($this->IsValidParam($request,"X_WNS_RequestForStatus"))
                $this->X_WNS_REQUESTFORSTATUS = $request;
            else
                throw new \InvalidArgumentException("The type must be a X_WNS_RequestForStatus value");
        }
        /**
         * Set the suppresspopup 
         * @param string $val
         */
        public function SetX_WNS_SUPRESSPOPUP($val)
        {
            if($this->IsValidParam($val,"X_WNS_SuppressPopup"))
                $this->X_WNS_SUPRESSPOPUP = $val;
            else
                throw new \InvalidArgumentException("The type must be a X_WNS_SuppressPopup value");
        }
        /**
         * Set the content type for notification header
         * @param string $val
         */
        public function SetContentType($val)
        {
            if($this->IsValidParam($val,"Content_Type"))
                $this->ContentType = $val;
            else
                throw new \InvalidArgumentException("The type must be a Content_Type value");
        }

        /**
         * Set notification tag
         * @param string $val
         */
        public function SetX_WNS_TAG($val)
        {
            if(is_string($val))
                $this->X_WNS_TAG = $val;
            else
                throw new \InvalidArgumentException("The type must be a string");
        }
        /**
         * Set the expiration time
         * @param int $val
         */
        public function SetX_WNS_TTL($val)
        {
            if(is_int($val))
                $this->X_WNS_TTL = $val;
            else
                throw new \InvalidArgumentException("The type must be an integer");
        }
        /**
         * Set the group
         * @param string $val
         */
        public function SetX_WNS_GROUP($val)
        {
            if(is_string($val))
                $this->X_WNS_GROUP = $val;
            else
                throw new \InvalidArgumentException("The type must be a string");
        }

        /**
         * Set the X-WNS-Match param. You shoud set array with the match type. 
         * X_WNS_Match::Tag -> (1)
         * X_WNS_Match::TagAndGroup -> (2)
         * X_WNS_Match::Group -> (3)
         * NOTE: If you don't want use match, don't call the set!
         * NOTE: If omitted the request will be ALL
         * NOTE: This work only on windows phone, if you send this header for windows notification, you'll get an error
         * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_match
         * @param int $match X_WNS_Match value
         * @param array $params array of type (1)->("tag"=>"value")|(2)->("tag"=>"value", "group" => "value")|(3)->("group"=>"value")
         * @throws  \InvalidArgumentException if the provided argouments are wrong
         */
        public function SetX_WNS_MATCH($match, $params = null)
        {
            if($this->IsValidParam($match,"X_WNS_Match"))
            {
                switch($match)
                {
                    case X_WNS_Match::Tag : 
                        { 
                            if(isset($params["tag"])) 
                            {
                                    $this->X_WNS_MATCH = "type=wns/toast;tag=".$params["tag"];
                                }
                            else
                                throw new \InvalidArgumentException("The params must contain tag definition for this match type");            
                            break;
                        }
                    case X_WNS_Match::Group : 
                        { 
                            if(isset($params["group"])) 
                            {
                                    $this->X_WNS_MATCH = "type=wns/toast;group=".$params["group"];
                                }
                            else
                                throw new \InvalidArgumentException("The params must contain group definition for this match type");            
                            break;
                        }
                    case X_WNS_Match::TagAndGroup : 
                        { 
                            if(isset($params["group"]) && isset($params["tag"])) 
                            {
                                    $this->X_WNS_MATCH = "type=wns/toast;group=".$params["group"].";tag=".$params["tag"];
                                }
                            else
                                throw new \InvalidArgumentException("The params must contain group and tag definition for this match type");            
                            break;
                        }
                    case X_WNS_Match::All : { $this->X_WNS_MATCH = "type=wns/toast;all"; break; }
                    default: $this->X_WNS_MATCH = null;
                }
            }
            else
                throw new \InvalidArgumentException("The match must be a X_WNS_Match value");
        }
        #region pure get
        /**
         * Get the token for push requests
         * @return OAuthObject The authorization token
         */
        public function GetAuthorization()
        {
            return $this->Authorization;
        }
        /**
         * Get the tile type
         * @return string Return the notification type
         */
        public function GetX_WNS_TYPE()
        {
            return $this->X_WNS_TYPE;
        }
        /**
         * Get the cache policy
         * @return string The cache policy
         */
        public function GetX_WNS_CACHE_POLICY()
        {
            return $this->X_WNS_CACHE_POLICY;
        }
        /**
         * Get the request for status header
         * @return string 
         */
        public function GetX_WNS_REQUESTFORSTATUS()
        {
            return $this->X_WNS_REQUESTFORSTATUS;
        }
        /**
         * Get the suppresspopup
         * @return string
         */
        public function GetX_WNS_SUPRESSPOPUP()
        {
            return $this->X_WNS_SUPRESSPOPUP;
        }
        
        /**
         * Get the content type 
         * @return string
         */
        public function GetContentType()
        {
            return $this->ContentType;
        }

        /**
         * Get the notification tag setting
         * @return string 
         */
        public function GetX_WNS_TAG()
        {
            return $this->X_WNS_TAG;
        }
        /**
         * Get the TTL setting
         * @return string
         */
        public function GetX_WNS_TTL()
        {
            return $this->X_WNS_TTL;
        }
        /**
         * Get the notification group
         * @return string
         */
        public function GetX_WNS_GROUP()
        {
            return $this->X_WNS_GROUP;
        }
        #endregion
        
        /**
         * Get the header for auth access the token for push requests 
         * @return string The authorization token
         */
        public function GetHeaderAuthorization()
        {
            return "Authorization: ".$this->Authorization->GetTokenType()." ".$this->Authorization->GetToken();
        }
        /**
         * Get the Header for the tile type
         * @return string Return the notification type
         */
        public function GetHeaderX_WNS_TYPE()
        {
            return "X-WNS-Type: ".$this->X_WNS_TYPE;
        }
        /**
         * GetHeader the cache policy
         * @return string The cache policy
         */
        public function GetHeaderX_WNS_CACHE_POLICY()
        {
            return "X-WNS-Cache-Policy: ".$this->X_WNS_CACHE_POLICY;
        }
        /**
         * GetHeader the request for status header
         * @return string 
         */
        public function GetHeaderX_WNS_REQUESTFORSTATUS()
        {
            return "X-WNS-RequestForStatus: ".$this->X_WNS_REQUESTFORSTATUS;
        }
        /**
         * GetHeader the suppresspopup
         * @return string
         */
        public function GetHeaderX_WNS_SUPRESSPOPUP()
        {
            return "X-WNS-SuppressPopup: ".$this->X_WNS_SUPRESSPOPUP;
        }
        
        /**
         * Get the Header for the content type 
         * @return string
         */
        public function GetHeaderContentType()
        {
            return "content-type: ".$this->ContentType;
        }
        /**
         * Return the content length
         * @param string the body 
         * @return string
         */
        public function GetHeaderContentLenght($body)
        {
            return "Content-length: ".strlen($body);
        }
        /**
         * GetHeader the notification tag setting
         * @return string 
         */
        public function GetHeaderX_WNS_TAG()
        {
            return "X-WNS-Tag: ".$this->X_WNS_TAG;
        }
        /**
         * GetHeader the TTL setting
         * @return string
         */
        public function GetHeaderX_WNS_TTL()
        {
            return "X-WNS-TTL: ".$this->X_WNS_TTL;
        }
        /**
         * GetHeader the notification group
         * @return 
         */
        public function GetHeaderX_WNS_GROUP()
        {
            return "X-WNS-Group: ".$this->X_WNS_GROUP;
        }
        
        /**
         * Get the X-WNS-Match param. You shoud Get array with the match type. 
         * NOTE: If omitted the request will be ALL
         * NOTE: This work only on windows phone, if you send this header for windows notification, you'll get an error
         * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_match
         * @throws  \InvalidArgumentException if the provided argouments are wrong
         * @return string
         */
        public function GetHeaderX_WNS_MATCH()
        {
            return "X-WNS-Match: ".$this->X_WNS_MATCH;
        }
        /**
         * Return the array with header setted for notification by the options settings
         * @return array Return the array with header setted for notification by the options settings
         */
        public function GetHeaderArray()
        {
            $result = array();
            $refl = new \ReflectionClass($this);
            foreach ($this as $key => $val)
            {
                if($val!==null)
            	    $result[$key] = $refl->getMethod("GetHeader".$key)->invoke($this);
            }
            return $result;
        }
        /**
         * Check if passed parameter belongs to a class
         * @param string $string value
         * @param string $class class name
         * @return true if yes, false elese
         */
        private function IsValidParam($string,$class)
        {
            $result = false;
            $class = $this->GetClassConstants(__NAMESPACE__."\\".$class);
            foreach ($class as $value)
            {
            	if($value == $string)
                {
                    $result = true;
                    break;
                }
            }
            return $result;
        }
        /**
         * Return class const from class name
         * @param string $class
         * @return array with constants
         */
        private function GetClassConstants($class)
        {
            $class = new \ReflectionClass($class);
            return $class->getConstants();
        }

    }
    
    
    #region enum definition
    /**
     * WNS supported methods
     */
    final class HTTPMethod
    {
        const __default = self::Post;
        const Post = "POST";
        const Delete = "DELETE";
    }
    /**
     * WNS headers option for content type
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_auth
     */
    final class Content_Type
    {
        const __default = self::Standard;
        const None = null;
        const Standard = "text/xml";
        const NotElab = "application/octet-stream";
    }
    
    /**
     * WNS headers option for notification type
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_type
     */
    final class X_WNS_Type 
    {
        const __default = self::Toast;
        const Toast = "wns/toast";
        const Badge = "wns/badge";
        const Raw = "wns/raw";
        const Tile = "wns/tile";
        const None = null;
    }
    
    /**
     * WNS headers option for cache policy
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_cache
     */
    final class X_WNS_Cache_Policy
    {        
        const __default = self::NotSet;
        const Cache = "cache";
        const NoCache = "no-cache";
        const NotSet = null;
    }
    
    /**
     * WNS headers option for request status.
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_request
     */
    final class X_WNS_RequestForStatus
    {
        const __default = self::NotSet;
        const Request = "true";
        const NotRequest = "false";
        const NotSet = null;
    }
    
    /**
     * WNS headers option for suppress popup
     * NOTE: This work only on windows phone, if you send this header for windows notification, you'll get an error
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_suppresspopup
     */
    final class X_WNS_SuppressPopup
    {
        const __default = self::NotSet;
        const Suppress = "true";
        const NotSuppress = "false";
        const NotSet = null;
    }
    /**
     * WNS Header option for notification delete
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#pncodes_x_wns_match
     * NOTE: This work only on windows phone, if you send this header for windows notification, you'll get an error
     */
    final class X_WNS_Match
    {
        const __default = self::All;
        const TagAndGroup = 0;
        const Tag = 1;
        const Group = 2;
        const All = 3;
        const None = null; 
    }
    
    /**
     * Authorization object
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx#requesting_and_receiving_an_access_token
     */
    final class OAuthObject
    {
        /**
         * OAuth token access
         * @var string
         */
        private $Token;
        /**
         * OAuth token type
         * @var string
         */
        private $TokenType;
        public function SetToken($val)
        {
            $this->Token = $val;
        }
        public function GetToken()
        {
            return $this->Token;
        }
        public function SetTokenType($val)
        {
            $this->TokenType = $val;
        }
        public function GetTokenType()
        {
            return $this->TokenType;
        }
        /**
         * Constructor, It can set also token and tokentype
         * @param array $oauth array of type ("token_type" => "value", "access_token" => "value")
         */
        public final function __construct($oauth = null)
        {
            if($oauth !== null)
            {
                if(isset($oauth["token_type"]) && isset($oauth["access_token"]))
                {
                    $this->SetTokenType($oauth["token_type"]);
                    $this->SetToken($oauth["access_token"]);
                }
                else
                    throw new \InvalidArgumentException("Array doesn't contains Token or TokenType");
            }
        }
    }
    #endregion
    
    
    #region notification type
    /**
     * Templates for toast
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh761494.aspx
     */
    final class TemplateToast
    {
        const Silent = '<audio silent="true" />';
        #region no-loop sounds
        const NotificationDefault = '<audio src="ms-winsoundevent:Notification.Default" loop="false" />';
        const NotificationIM = '<audio src="ms-winsoundevent:Notification.IM" loop="false" />';
        const NotificationMail = '<audio src="ms-winsoundevent:Notification.Mail" loop="false" />';
        const NotificationReminder = '<audio src="ms-winsoundevent:Notification.Reminder" loop="false" />';
        const NotificationSms = '<audio src="ms-winsoundevent:Notification.SMS" loop="false" />';
        #endregion
        #region loop sounds
        const NotificationLoopingAlarm = '<audio src="ms-winsoundevent:Notification.Looping.Alarm" loop="true" />';
        const NotificationLoopingAlarm2 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm2" loop="true" />';
        const NotificationLoopingAlarm3 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm3" loop="true" />';
        const NotificationLoopingAlarm4 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm4" loop="true" />';
        const NotificationLoopingAlarm5 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm5" loop="true" />';
        const NotificationLoopingAlarm6 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm6" loop="true" />';
        const NotificationLoopingAlarm7 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm7" loop="true" />';
        const NotificationLoopingAlarm8 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm8" loop="true" />';
        const NotificationLoopingAlarm9 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm9" loop="true" />';
        const NotificationLoopingAlarm10 = '<audio src="ms-winsoundevent:Notification.Looping.Alarm10" loop="true" />';
        const NotificationLoopingCall = '<audio src="ms-winsoundevent:Notification.Looping.Call" loop="true" />';
        const NotificationLoopingCall2 = '<audio src="ms-winsoundevent:Notification.Looping.Call2" loop="true" />';
        const NotificationLoopingCall3 = '<audio src="ms-winsoundevent:Notification.Looping.Call3" loop="true" />';
        const NotificationLoopingCall4 = '<audio src="ms-winsoundevent:Notification.Looping.Call4" loop="true" />';
        const NotificationLoopingCall5 = '<audio src="ms-winsoundevent:Notification.Looping.Call5" loop="true" />';
        const NotificationLoopingCall6 = '<audio src="ms-winsoundevent:Notification.Looping.Call6" loop="true" />';
        const NotificationLoopingCall7 = '<audio src="ms-winsoundevent:Notification.Looping.Call7" loop="true" />';
        const NotificationLoopingCall8 = '<audio src="ms-winsoundevent:Notification.Looping.Call8" loop="true" />';
        const NotificationLoopingCall9 = '<audio src="ms-winsoundevent:Notification.Looping.Call9" loop="true" />';
        const NotificationLoopingCall10 = '<audio src="ms-winsoundevent:Notification.Looping.Call10" loop="true" />';
        #endregion
        /**
         * Return the custom audio xml for the toast template
         * @param string $url local audio resource url
         * @param $loop = false true if it's a loop sound, default is false, no-loop sound
         * @return string
         */
        public static function CustomSound($url,$loop = false)
        {
            return '<audio src="'.$url.'" loop="'.($loop ? "true" : "false").'" />';
        }
        /**
         * Return the duration attribute
         * @param string $sound
         * @return string
         */
        private static function SoundDuration($sound)
        {
            $duration = "";
            if($sound != TemplateToast::Silent)
            {
                $duration = simplexml_load_string($sound);
                if($duration["loop"] == "true")
                    $duration = 'duration="long"';
                else
                    $duration = "";
            }
            return $duration;
        }
        /**
         * Return xml for ToastText01
         * @param string $bodyText
         * @param string $sound
         * @return string
         */
        public static function ToastText01($bodyText, $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?>
                        <toast '.TemplateToast::SoundDuration($sound).' >
                            <visual>
                                <binding template="ToastText01">
                                    <text id="1">'.$bodyText.'</text>
                                </binding>
                            </visual>'.$sound.'
                        </toast>';
        }
        /**
         * Return xml for ToastText02
         * @param string headLineText
         * @param string $bodyText
         * @param string $sound
         * @return string
         */
        public static function ToastText02($headLineText,$bodyText, $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastText02"><text id="1">'.$headLineText.'</text><text id="2">'.$bodyText.'</text></binding></visual>'.$sound.'</toast>';
        }
        /**
         * Return xml for ToastText03
         * @param string headLineText
         * @param string $bodyText
         * @param string $sound
         * @return string
         */
        public static function ToastText03($headLineText,$bodyText, $sound = TemplateToast::NotificationDefault)
        {
                return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastText03"><text id="1">'.$headLineText.'</text><text id="2">'.$bodyText.'</text></binding></visual>'.$sound.'</toast>';
        }
        /**
         * Return xml for ToastText04
         * @param string headLineText
         * @param string $bodyText1
         * @param string $bodyText2
         * @param string $sound
         * @return string
         */
        public static function ToastText04($headLineText,$bodyText1,$bodyText2, $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastText04"><text id="1">'.$headLineText.'</text><text id="2">'.$bodyText1.'</text><text id="3">'.$bodyText2.'</text></binding></visual>'.$sound.'</toast>';
        }
        /**
         * Retirm xml for ToastImageAndText01
         * @param $bodyText
         * @param string $src Image source (http:// or https:// : A web-based image.) | (ms-appx:/// : An image included in the app package.) | (ms-appdata:///local/ : An image saved to local storage.) | (file:/// : A local image. (Only supported for desktop apps.))
         * @param string $alt = ""
         * @param string $sound
         * @return string
         */
        public static function ToastImageAndText01($bodyText,$src, $alt = "", $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastImageAndText01"><image id="1" src="'.$src.'" alt="'.$alt.'"/><text id="1">'.$bodyText.'</text></binding></visual>'.$sound.'</toast>';
        }
        /**
         * Retirm xml for ToastImageAndText02
         * @param string headLineText
         * @param $bodyText
         * @param string $src Image source (http:// or https:// : A web-based image.) | (ms-appx:/// : An image included in the app package.) | (ms-appdata:///local/ : An image saved to local storage.) | (file:/// : A local image. (Only supported for desktop apps.))
         * @param string $alt = ""
         * @param string $sound
         * @return string
         */
        public static function ToastImageAndText02($headLineText,$bodyText,$src, $alt="", $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastImageAndText02"><image id="1" src="'.$src.'" alt="'.$alt.'"/><text id="1">'.$headLineText.'</text><text id="2">'.$bodyText.'</text></binding></visual>'.$sound.'</toast>';
        }
        /**
         * Retirm xml for ToastImageAndText03
         * @param $bodyText
         * @param string headLineText
         * @param string $src Image source (http:// or https:// : A web-based image.) | (ms-appx:/// : An image included in the app package.) | (ms-appdata:///local/ : An image saved to local storage.) | (file:/// : A local image. (Only supported for desktop apps.))
         * @param string $alt = ""
         * @param string $sound 
         * @return string
         */
        public static function ToastImageAndText03($headLineText,$bodyText,$src,$alt = "", $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastImageAndText03"><image id="1" src="'.$src.'" alt="'.$alt.'"/><text id="1">'.$headLineText.'</text><text id="2">'.$bodyText.'</text></binding></visual>'.$sound.'</toast>';
        }
        /**
         * Retirm xml for ToastImageAndText04
         * @param string headLineText
         * @param $bodyText1
         * @param $bodyText2
         * @param string $src Image source (http:// or https:// : A web-based image.) | (ms-appx:/// : An image included in the app package.) | (ms-appdata:///local/ : An image saved to local storage.) | (file:/// : A local image. (Only supported for desktop apps.))
         * @param string $alt = ""
         * @param string $sound
         * @return string
         */
        public static function ToastImageAndText04($headLineText,$bodyText1,$bodyText2,$src,$alt = "", $sound = TemplateToast::NotificationDefault)
        {
            return '<?xml version="1.0" encoding="utf-8"?><toast '.TemplateToast::SoundDuration($sound).' ><visual><binding template="ToastImageAndText04"><image id="1" src="'.$src.'" alt="'.$alt.'"/><text id="1">'.$headLineText.'</text><text id="2">'.$bodyText1.'</text><text id="3">'.$bodyText2.'</text></binding></visual>'.$sound.'</toast>';
        }
        
    }
    /**
     * Templates for badges
     * @see https://msdn.microsoft.com/en-us/library/windows/apps/hh779719.aspx
     */
    final class TemplateBadge
    {
        const NoBadge = '<?xml version="1.0" encoding="utf-8"?><badge value="none"/>';
        const Activity = '<?xml version="1.0" encoding="utf-8"?><badge value="activity"/>';
        const Alarm = '<?xml version="1.0" encoding="utf-8"?><badge value="alarm"/>';
        const Alert = '<?xml version="1.0" encoding="utf-8"?><badge value="alert"/>';
        const Attention = '<?xml version="1.0" encoding="utf-8"?><badge value="attention"/>';
        const Available = '<?xml version="1.0" encoding="utf-8"?><badge value="available"/>';
        const Away = '<?xml version="1.0" encoding="utf-8"?><badge value="away"/>';
        const Busy = '<?xml version="1.0" encoding="utf-8"?><badge value="busy"/>';
        const Error = '<?xml version="1.0" encoding="utf-8"?><badge value="error"/>';
        const NewMessage = '<?xml version="1.0" encoding="utf-8"?><badge value="newMessage"/>';
        const Paused = '<?xml version="1.0" encoding="utf-8"?><badge value="paused"/>';
        const Playing = '<?xml version="1.0" encoding="utf-8"?><badge value="playing"/>';
        const Unavailable = '<?xml version="1.0" encoding="utf-8"?><badge value="unavailable"/>';
        /**
         * Return the badge template for numeric values
         * @param integer $val A non-negative integer
         * @return string 
         */
        public static function NumericBadge($val)
        {
            if(!is_int($val) || $val < 0)
                $val = 0;
            return '<?xml version="1.0" encoding="utf-8"?><badge value="'.$val.'"/>';
        }
    }
    
    /* to evaluate for version, multi-binding and the high number of tile type\combination
    class TemplateTile
    {
    }
    
     */
    #endregion
}

/*
Microsoft Public License (Ms-PL)

This license governs use of the accompanying software. If you use the software, you accept this license. If you do not accept the license, do not use the software.

1. Definitions

The terms "reproduce," "reproduction," "derivative works," and "distribution" have the same meaning here as under U.S. copyright law.

A "contribution" is the original software, or any additions or changes to the software.

A "contributor" is any person that distributes its contribution under this license.

"Licensed patents" are a contributor's patent claims that read directly on its contribution.

2. Grant of Rights

(A) Copyright Grant- Subject to the terms of this license, including the license conditions and limitations in section 3, each contributor grants you a non-exclusive, worldwide, royalty-free copyright license to reproduce its contribution, prepare derivative works of its contribution, and distribute its contribution or any derivative works that you create.

(B) Patent Grant- Subject to the terms of this license, including the license conditions and limitations in section 3, each contributor grants you a non-exclusive, worldwide, royalty-free license under its licensed patents to make, have made, use, sell, offer for sale, import, and/or otherwise dispose of its contribution in the software or derivative works of the contribution in the software.

3. Conditions and Limitations

(A) No Trademark License- This license does not grant you rights to use any contributors' name, logo, or trademarks.

(B) If you bring a patent claim against any contributor over patents that you claim are infringed by the software, your patent license from such contributor to the software ends automatically.

(C) If you distribute any portion of the software, you must retain all copyright, patent, trademark, and attribution notices that are present in the software.

(D) If you distribute any portion of the software in source code form, you may do so only under this license by including a complete copy of this license with your distribution. If you distribute any portion of the software in compiled or object code form, you may only do so under a license that complies with this license.

(E) The software is licensed "as-is." You bear the risk of using it. The contributors give no express warranties, guarantees or conditions. You may have additional consumer rights under your local laws which this license cannot change. To the extent permitted under your local laws, the contributors exclude the implied warranties of merchantability, fitness for a particular purpose and non-infringement.
 */