<?php
/*This class adds a button to the login form allowing the user to login with onelogin
in addition to the wordpress login.
*/
class OLLoginButton
{
    protected $redirect_to = '';
    public function __construct()
    {
        add_action('init', array($this, 'login_form'));
        add_action('init', array($this, 'redirect_to_ol'));

        add_filter('login_redirect', array($this, 'get_login_redirect'), 100, 3);
    }
    public function login_form()
    {
        add_action('login_form', array($this, 'add_onelongin_button'));
    }
    public function get_login_redirect($redirect_to, $requested_redirect_to, $user)
    {
        if ($user->ID == 0) {
            $this->redirect_to = $redirect_to;
        } else {
            wp_redirect($redirect_to);
            die();
        }

        return $redirect_to;
    }
    public function add_onelongin_button()
    {
        /*
        Filters are used so that you can change the siteurl and redirect.
        This is helpful if you have a subdomain multisite that is using domain mapping
        but you want to use a single onelogin app for all. */
        if (function_exists('initialize_saml')) {
            $site_url = wp_login_url();
            $site_url = apply_filters('ol_loginbutton_siteurl', $site_url);
            if ($this->redirect_to != '') {
                $redirect = $this->redirect_to;
                $redirect = apply_filters('ol_loginbutton_redirect', $redirect);
                $url = $site_url.'?ForceOL='.$redirect;
            } else {
                $url = $site_url.'?ForceOL=true';
            }
            $image_url = plugins_url('OneLoginButton.png', __FILE__);
            /*
            This adds the button to the form and then the javascript makes sure it's under the normal login button.
            The original just added to the form and people would click it because it was the first button and not
            because they were OneLogin users. Little hacky but it works */
            echo <<<EOT
            <a title="Login With OneLogin" id="oneLoginButton" href="$url"><img style="width:100%" alt="Login With OneLogin" src="$image_url" border='0'/></a>
            <style>
                #wp-submit {
                    width: 100%;
                    height: 50px;
                    font-size: 18px;
                }
            </style>
            <script>
setTimeout(function(){
var d = document.getElementById('oneLoginButton');
d.parentNode.appendChild(d);
},200);
</script>
EOT;
        }
    }

    public function redirect_to_ol()
    {
        if (function_exists('initialize_saml') && isset($_GET['ForceOL']) && !is_user_logged_in()) {
            if (!filter_var($_GET['ForceOL'], FILTER_VALIDATE_URL) === false) {
                //Valid URL
                $url = $_GET['ForceOL'];
            } else {
                $url = $_SERVER['REQUEST_URI'];
            }
            $auth = initialize_saml();
            if (!empty($url)) {
                $auth->login($url);
            } else {
                $auth->login();
            }
        }
    }
}
