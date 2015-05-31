<?php

if ( !class_exists( 'ICWP_EmailProcessor_V1', false ) ):

require_once( dirname(__FILE__).ICWP_DS.'base.php' );

class ICWP_EmailProcessor_V1 extends ICWP_WPSF_Processor_Base {

	const Slug = 'email';
	
	protected $m_sRecipientAddress;
	protected $m_sSiteName;

	/**
	 * @var string
	 */
	static protected $sModeFile_EmailThrottled;
	/**
	 * @var int
	 */
	static protected $nThrottleInterval = 1; 
	/**
	 * @var int
	 */
	protected $m_nEmailThrottleLimit;
	/**
	 * @var int
	 */
	protected $m_nEmailThrottleTime;
	/**
	 * @var int
	 */
	protected $m_nEmailThrottleCount;
	/**
	 * @var boolean
	 */
	protected $fEmailIsThrottled;

	/**
	 * @param ICWP_WPSF_FeatureHandler_Email $oFeatureOptions
	 */
	public function __construct( ICWP_WPSF_FeatureHandler_Email $oFeatureOptions ) {
		parent::__construct( $oFeatureOptions );
	}
	
	public function reset() {
		parent::reset();
		self::$sModeFile_EmailThrottled = dirname( __FILE__ ).'/../mode.email_throttled';
	}

	public function run() {}
	
	/**
	 * @param string $sEmailAddress
	 * @param string $sEmailSubject
	 * @param array $aMessage
	 * @return boolean
	 * @uses wp_mail
	 */
	public function sendEmailTo( $sEmailAddress = '', $sEmailSubject = '', $aMessage = array() ) {

		$sEmailTo = $this->verifyEmailAddress( $sEmailAddress );

		$aHeaders = array(
			'MIME-Version: 1.0',
			'Content-type: text/plain;',
			sprintf( 'From: %s :: %s <%s>', $this->getSiteName(), $this->getController()->getHumanName(), $sEmailTo ),
			sprintf( "Subject: %s", $sEmailSubject ),
			'X-Mailer: PHP/'.phpversion()
		);
		
		$this->updateEmailThrottle();
		// We make it appear to have "succeeded" if the throttle is applied.
		if ( $this->fEmailIsThrottled ) {
			return true;
		}
		$fSuccess = wp_mail( $sEmailTo, $sEmailSubject, implode( "\r\n", $aMessage ), implode( "\r\n", $aHeaders ) );
		return $fSuccess;
	}

	/**
	 * Will send email to the default recipient setup in the object.
	 *
	 * @param string $sEmailSubject
	 * @param array $aMessage
	 * @return boolean
	 */
	public function sendEmail( $sEmailSubject, $aMessage ) {
		return $this->sendEmailTo( null, $sEmailSubject, $aMessage );
	}

	/**
	 * Whether we're throttled is dependent on 2 signals.  The time interval has changed, or the there's a file
	 * system object telling us we're throttled.
	 * 
	 * The file system object takes precedence.
	 * 
	 * @return boolean
	 */
	protected function updateEmailThrottle() {

		// Throttling Is Effectively Off
		if ( $this->getThrottleLimit() <= 0 ) {
			$this->setThrottledFile( false );
			return $this->fEmailIsThrottled;
		}
		
		// Check that there is an email throttle file. If it exists and its modified time is greater than the 
		// current $this->m_nEmailThrottleTime it suggests another process has touched the file and updated it
		// concurrently. So, we update our $this->m_nEmailThrottleTime accordingly.
		if ( is_file( self::$sModeFile_EmailThrottled ) ) {
			$nModifiedTime = filemtime( self::$sModeFile_EmailThrottled );
			if ( $nModifiedTime > $this->m_nEmailThrottleTime ) {
				$this->m_nEmailThrottleTime = $nModifiedTime;
			}
		}
		
		if ( !isset($this->m_nEmailThrottleTime) || $this->m_nEmailThrottleTime > $this->time() ) {
			$this->m_nEmailThrottleTime = $this->time();
		}
		if ( !isset($this->m_nEmailThrottleCount) ) {
			$this->m_nEmailThrottleCount = 0;
		}
		
		// If $nNow is greater than throttle interval (1s) we turn off the file throttle and reset the count
		$nDiff = $this->time() - $this->m_nEmailThrottleTime;
		if ( $nDiff > self::$nThrottleInterval ) {
			$this->m_nEmailThrottleTime = $this->time();
			$this->m_nEmailThrottleCount = 1;	//we set to 1 assuming that this was called because we're about to send, or have just sent, an email.
			$this->setThrottledFile( false );
		}
		else if ( is_file( self::$sModeFile_EmailThrottled ) || ( $this->m_nEmailThrottleCount >= $this->getThrottleLimit() ) ) {
			$this->setThrottledFile( true );
		}
		else {
			$this->m_nEmailThrottleCount++;
		}
	}
	
	public function setThrottledFile( $infOn = false ) {
		
		$this->fEmailIsThrottled = $infOn;
		
		if ( $infOn && !is_file( self::$sModeFile_EmailThrottled ) && function_exists('touch') ) {
			@touch( self::$sModeFile_EmailThrottled );
		}
		else if ( !$infOn && is_file(self::$sModeFile_EmailThrottled) ) {
			@unlink( self::$sModeFile_EmailThrottled );
		}
	}
	
	public function setDefaultRecipientAddress( $insEmailAddress ) {
		$this->m_sRecipientAddress = $insEmailAddress;
	}

	/**
	 * @param string $sEmailAddress
	 * @return string
	 */
	public function verifyEmailAddress( $sEmailAddress = '' ) {
		return ( empty( $sEmailAddress ) || !is_email( $sEmailAddress ) ) ? $this->getPluginDefaultRecipientAddress() : $sEmailAddress;
	}

	/**
	 * @return string
	 */
	public function getSiteName() {
		return $this->loadWpFunctionsProcessor()->getSiteName();
	}
	
	public function getThrottleLimit() {
		if ( empty( $this->m_nEmailThrottleLimit ) ) {
			$this->m_nEmailThrottleLimit = $this->getOption( 'send_email_throttle_limit' );
		}
		return $this->m_nEmailThrottleLimit;
	}
}

endif;

if ( !class_exists( 'ICWP_WPSF_Processor_Email', false ) ):
	class ICWP_WPSF_Processor_Email extends ICWP_EmailProcessor_V1 { }
endif;