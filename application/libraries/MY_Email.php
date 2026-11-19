<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Email, but only sent from production (outbound_is_live(),
 * application/helpers/outbound_helper.php). Anywhere else send() logs that
 * it didn't send, clears the message like a real send would, and returns
 * TRUE, so calling code behaves as usual without anything leaving the
 * machine.
 *
 * Nothing in the app sends email today; this is here so that it stays safe
 * if something ever does ($this->load->library('email') loads this class).
 */
class MY_Email extends CI_Email
{
	public function send($auto_clear = TRUE)
	{
		if ( ! function_exists('outbound_is_live'))
		{
			get_instance()->load->helper('outbound');
		}

		if ( ! outbound_is_live(ENVIRONMENT))
		{
			$recipients = is_array($this->_recipients) ? count($this->_recipients) : (int) ($this->_recipients !== '');
			log_message('info', 'Email NOT SENT ('.ENVIRONMENT.'): blocked outside production; recipients='
				.$recipients.', subject length='.strlen((string) $this->_subject));
			if ($auto_clear)
			{
				$this->clear();
			}
			return TRUE;
		}

		return parent::send($auto_clear);
	}
}
