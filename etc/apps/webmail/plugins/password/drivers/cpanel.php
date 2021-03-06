<?php

/**
 * cPanel Password Driver
 *
 * Driver that adds functionality to change the users cPanel password.
 * Originally written by Fulvio Venturelli <fulvio@venturelli.org>
 *
 * Completely rewritten using the cPanel API2 call Email::passwdpop
 * as opposed to the original coding against the UI, which is a fragile method that
 * makes the driver to always return a failure message for any language other than English
 * see http://trac.roundcube.net/ticket/1487015
 *
 * This driver has been tested with o2switch hosting and seems to work fine.
 *
 * @version 3.0
 * @author Christian Chech <christian@chech.fr>
 */

class rcube_cpanel_password
{
    public function save($curpas, $newpass)
    {
        require_once 'xmlapi.php';

        $rcmail = rcmail::get_instance();

        $this->cuser = $rcmail->config->get('password_cpanel_username');

        // Setup the xmlapi connection
        $this->xmlapi = new xmlapi($rcmail->config->get('password_cpanel_host'));
        $this->xmlapi->set_port($rcmail->config->get('password_cpanel_port'));
        $this->xmlapi->password_auth($this->cuser, $rcmail->config->get('password_cpanel_password'));
        $this->xmlapi->set_output('json');
        $this->xmlapi->set_debug(0);

        if ($this->setPassword($_SESSION['username'], $newpass)) {
            return PASSWORD_SUCCESS;
        }
        else {
            return PASSWORD_ERROR;
        }
    }

    /**
     * Change email account password
     *
     * Returns true on success or false on failure.
     * @param string $password email account password
     * @return bool
     */
    function setPassword($address, $password)
    {
        if (strpos($address, '@')) {
            list($data['email'], $data['domain']) = explode('@', $address);
        }
        else {
            list($data['email'], $data['domain']) = array($address, '');
        }

        $data['password'] = $password;

        $query = $this->xmlapi->api2_query($this->cuser, 'Email', 'passwdpop', $data);
        $query = json_decode($query, true);

        if ($query['cpanelresult']['data'][0]['result'] == 1) {
            return true;
        }

        return false;
    }
}
