<?php
/**
 * Copyright (c) 2015 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

if ( !class_exists( 'ICWP_WPSF_WpFunctions_V7', false ) ):

	class ICWP_WPSF_WpFunctions_V7 {
		/**
		 * @var ICWP_WPSF_WpDb
		 */
		private static $oWpDb;

		/**
		 * @var WP_Automatic_Updater
		 */
		protected $oWpAutomaticUpdater;

		/**
		 * @var ICWP_WPSF_WpFunctions_V7
		 */
		protected static $oInstance = NULL;

		/**
		 * @return ICWP_WPSF_WpFunctions_V7
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		/**
		 * @var string
		 */
		protected $sWpVersion;

		/**
		 * @var boolean
		 */
		protected $bIsMultisite;

		public function __construct() {}

		/**
		 * @param WP_Post $oPost
		 *
		 * @return bool
		 */
		public function comments_getIfCommentsOpen( $oPost = null ) {
			if ( is_null( $oPost ) ) {
				global $post;
				$oPost = $post;
			}
			return $oPost->comment_status == 'open';
		}

		/**
		 * @param string $sAuthorEmail
		 *
		 * @return bool
		 */
		public function comments_getIfCommentAuthorPreviouslyApproved( $sAuthorEmail ) {

			if ( empty( $sAuthorEmail ) || !is_email( $sAuthorEmail ) ) {
				return false;
			}

			$oDb = $this->loadDbProcessor();
			$sQuery = "
				SELECT comment_approved
				FROM %s
				WHERE
					comment_author_email = '%s'
					AND comment_approved = '1'
					LIMIT 1
			";

			$sQuery = sprintf(
				$sQuery,
				$oDb->getTable_Comments(),
				$sAuthorEmail
			);
			return $oDb->getVar( $sQuery ) == 1;
		}

		/**
		 * @return bool
		 */
		public function comments_getIsCommentPost() {
			return $this->loadDataProcessor()->GetIsRequestPost() && $this->getIsCurrentPage( 'wp-comments-post.php' );
		}

		/**
		 * @return null|string
		 */
		public function findWpLoad() {
			return $this->findWpCoreFile( 'wp-load.php' );
		}

		/**
		 * @param $sFilename
		 * @return null|string
		 */
		public function findWpCoreFile( $sFilename ) {
			$sLoaderPath	= dirname( __FILE__ );
			$nLimiter		= 0;
			$nMaxLimit		= count( explode( DIRECTORY_SEPARATOR, trim( $sLoaderPath, DIRECTORY_SEPARATOR ) ) );
			$bFound			= false;

			do {
				if ( @is_file( $sLoaderPath.DIRECTORY_SEPARATOR.$sFilename ) ) {
					$bFound = true;
					break;
				}
				$sLoaderPath = realpath( $sLoaderPath.DIRECTORY_SEPARATOR.'..' );
				$nLimiter++;
			}
			while ( $nLimiter < $nMaxLimit );

			return $bFound ? $sLoaderPath.DIRECTORY_SEPARATOR.$sFilename : null;
		}

		/**
		 * @param string $sRedirect
		 *
		 * @return bool
		 */
		public function doForceRunAutomaticUpdates( $sRedirect = '' ) {

			$lock_name = 'auto_updater.lock'; //ref: /wp-admin/includes/class-wp-upgrader.php
			delete_option( $lock_name );
			if ( !defined('DOING_CRON') ) {
				define( 'DOING_CRON', true ); // this prevents WP from disabling plugins pre-upgrade
			}

			// does the actual updating
			wp_maybe_auto_update();

			if ( !empty( $sRedirect ) ) {
				$this->doRedirect( network_admin_url( $sRedirect ) );
			}
			return true;
		}

		/**
		 * @return bool
		 */
		public function getIsRunningAutomaticUpdates() {
			return ( get_option( 'auto_updater.lock' ) ? true : false );
		}

		/**
		 * The full plugin file to be upgraded.
		 *
		 * @param string $sPluginFile
		 * @return boolean
		 */
		public function doPluginUpgrade( $sPluginFile ) {

			if ( !$this->getIsPluginUpdateAvailable( $sPluginFile )
				|| ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'update.php' ) ) {
				return true;
			}
			$sUrl = $this->getPluginUpgradeLink( $sPluginFile );
			wp_redirect( $sUrl );
			exit();
		}

		/**
		 * Clears any WordPress caches
		 */
		public function doBustCache() {
			global $_wp_using_ext_object_cache, $wp_object_cache;
			$_wp_using_ext_object_cache = false;
			if( !empty( $wp_object_cache ) ) {
				@$wp_object_cache->flush();
			}
		}

		/**
		 * @return bool
		 */
		public function getIsPermalinksEnabled() {
			return ( $this->getOption( 'permalink_structure' ) ? true : false );
		}

		/**
		 * @return array
		 */
		public function getPlugins() {
			if ( !function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			return function_exists( 'get_plugins' ) ? get_plugins() : array();
		}

		/**
		 * @param string $sRootPluginFile - the full path to the root plugin file
		 * @return array|null
		 */
		public function getPluginData( $sRootPluginFile ) {
			if ( !function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			return function_exists( 'get_plugin_data' ) ? get_plugin_data( $sRootPluginFile, false, false ) : array();
		}

		/**
		 * @param string $sPluginFile
		 * @return stdClass|null
		 */
		public function getPluginUpdateInfo( $sPluginFile ) {
			$aUpdates = $this->getWordpressUpdates();
			return ( !empty( $aUpdates ) && isset( $aUpdates[ $sPluginFile ] ) ) ? $aUpdates[ $sPluginFile ] : null;
		}

		/**
		 * @param string $sPluginFile
		 * @return string
		 */
		public function getPluginUpdateNewVersion( $sPluginFile ) {
			$oInfo = $this->getPluginUpdateInfo( $sPluginFile );
			return ( !is_null( $oInfo ) && isset( $oInfo->new_version ) ) ? $oInfo->new_version : '';
		}

		/**
		 * @param string $sPluginFile
		 * @return boolean|stdClass
		 */
		public function getIsPluginUpdateAvailable( $sPluginFile ) {
			$oInfo = $this->getPluginUpdateInfo( $sPluginFile );
			return !is_null( $oInfo );
		}

		/**
		 * @param string $sCompareString
		 * @param string $sKey
		 * @return bool
		 */
		public function getIsPluginActive( $sCompareString, $sKey = 'Name' ) {
			$sPluginFile = $this->getIsPluginInstalled( $sCompareString, $sKey );
			if ( !$sPluginFile ) {
				return false;
			}
			return is_plugin_active( $sPluginFile ) ? $sPluginFile : false;
		}

		/**
		 * @param string $sCompareString
		 * @param string $sKey
		 * @return bool|string
		 */
		public function getIsPluginInstalled( $sCompareString, $sKey = 'Name' ) {
			$aPlugins = $this->getPlugins();
			if ( empty( $aPlugins ) || !is_array( $aPlugins ) ) {
				return false;
			}

			foreach( $aPlugins as $sBaseFileName => $aPluginData ) {
				if ( isset( $aPluginData[$sKey] ) && $sCompareString == $aPluginData[$sKey] ) {
					return $sBaseFileName;
				}
			}
			return false;
		}

		/**
		 * @param string $sPluginBaseFile
		 * @return bool
		 */
		public function getIsPluginInstalledByFile( $sPluginBaseFile ) {
			$aPlugins = $this->getPlugins();
			return isset( $aPlugins[$sPluginBaseFile] );
		}

		/**
		 * @param string $sPluginFile
		 * @return string
		 */
		public function getPluginActivateLink( $sPluginFile ) {
			$sUrl = self_admin_url( 'plugins.php' ) ;
			$aQueryArgs = array(
				'action' 	=> 'activate',
				'plugin'	=> urlencode( $sPluginFile ),
				'_wpnonce'	=> wp_create_nonce( 'activate-plugin_' . $sPluginFile )
			);
			return add_query_arg( $aQueryArgs, $sUrl );
		}

		/**
		 * @param string $sPluginFile
		 * @return string
		 */
		public function getPluginDeactivateLink( $sPluginFile ) {
			$sUrl = self_admin_url( 'plugins.php' ) ;
			$aQueryArgs = array(
				'action' 	=> 'deactivate',
				'plugin'	=> urlencode( $sPluginFile ),
				'_wpnonce'	=> wp_create_nonce( 'deactivate-plugin_' . $sPluginFile )
			);
			return add_query_arg( $aQueryArgs, $sUrl );
		}

		/**
		 * @param string $sPluginFile
		 * @return string
		 */
		public function getPluginUpgradeLink( $sPluginFile ) {
			$sUrl = self_admin_url( 'update.php' ) ;
			$aQueryArgs = array(
				'action' 	=> 'upgrade-plugin',
				'plugin'	=> urlencode( $sPluginFile ),
				'_wpnonce'	=> wp_create_nonce( 'upgrade-plugin_' . $sPluginFile )
			);
			return add_query_arg( $aQueryArgs, $sUrl );
		}

		/**
		 * @param string $sType - plugins, themes
		 * @return array
		 */
		public function getWordpressUpdates( $sType = 'plugins' ) {
			$oCurrent = $this->getTransient( 'update_'.$sType );
			return ( is_object( $oCurrent ) && isset( $oCurrent->response ) ) ? $oCurrent->response : array();
		}

		/**
		 * @param string $sKey
		 * @return object
		 */
		public function getTransient( $sKey ) {

			// TODO: Handle multisite

			if ( version_compare( $this->getWordpressVersion(), '2.7.9', '<=' ) ) {
				return get_option( $sKey );
			}

			if ( function_exists( 'get_site_transient' ) ) {
				$mResult = get_site_transient( $sKey );
				if ( empty( $mResult ) ) {
					remove_all_filters( 'pre_site_transient_'.$sKey );
					$mResult = get_site_transient( $sKey );
				}
				return $mResult;
			}

			if ( version_compare( $this->getWordpressVersion(), '2.9.9', '<=' ) ) {
				return apply_filters( 'transient_'.$sKey, get_option( '_transient_'.$sKey ) );
			}

			return apply_filters( 'site_transient_'.$sKey, get_option( '_site_transient_'.$sKey ) );
		}

		/**
		 * @param string $sKey
		 * @param mixed $mValue
		 * @param int $nExpire
		 */
		public function setTransient( $sKey, $mValue, $nExpire = 0 ) {
			set_site_transient( $sKey, $mValue, $nExpire );
		}

		/**
		 * @param $sKey
		 *
		 * @return bool
		 */
		public function deleteTransient( $sKey ) {

			if ( version_compare( $this->getWordpressVersion(), '2.7.9', '<=' ) ) {
				return delete_option( $sKey );
			}

			if ( function_exists( 'delete_site_transient' ) ) {
				return delete_site_transient( $sKey );
			}

			if ( version_compare( $this->getWordpressVersion(), '2.9.9', '<=' ) ) {
				return delete_option( '_transient_'.$sKey );
			}

			return delete_option( '_site_transient_'.$sKey );
		}

		/**
		 * @param string $sPluginBaseFilename
		 *
		 * @return null|stdClass
		 */
		public function getPluginDataAsObject( $sPluginBaseFilename ){
			$aPlugins = get_plugins();
			if ( !isset( $aPlugins[$sPluginBaseFilename] ) || !is_array( $aPlugins[$sPluginBaseFilename] ) ) {
				return null;
			}

			return $this->loadDataProcessor()->convertArrayToStdClass( $aPlugins[ $sPluginBaseFilename ] );
		}

		/**
		 * @return string
		 */
		public function getWordpressVersion() {

			if ( empty( $this->sWpVersion ) ) {
				$sVersionFile = ABSPATH.WPINC.'/version.php';
				$sVersionContents = file_get_contents( $sVersionFile );

				if ( preg_match( '/wp_version\s=\s\'([^(\'|")]+)\'/i', $sVersionContents, $aMatches ) ) {
					$this->sWpVersion = $aMatches[1];
				}
				else {
					global $wp_version;
					$this->sWpVersion = $wp_version;
				}
			}
			return $this->sWpVersion;
		}

		/**
		 * @param string $sVersionToMeet
		 *
		 * @return boolean
		 */
		public function getWordpressIsAtLeastVersion( $sVersionToMeet ) {
			return version_compare( $this->getWordpressVersion(), $sVersionToMeet, '>=' );
		}

		/**
		 * @param string $sPluginBaseFilename
		 *
		 * @return boolean
		 */
		public function getIsPluginAutomaticallyUpdated( $sPluginBaseFilename ) {
			$oUpdater = $this->getWpAutomaticUpdater();
			if ( !$oUpdater ) {
				return false;
			}

			// This is due to a change in the filter introduced in version 3.8.2
			if ( $this->getWordpressIsAtLeastVersion( '3.8.2' ) ) {
				$mPluginItem = new stdClass();
				$mPluginItem->plugin = $sPluginBaseFilename;
			}
			else {
				$mPluginItem = $sPluginBaseFilename;
			}

			return $oUpdater->should_update( 'plugin', $mPluginItem, WP_PLUGIN_DIR );
		}

		/**
		 * @param array $aQueryParams
		 */
		public function redirectToLogin( $aQueryParams = array() ) {
			$this->doRedirect( wp_login_url(), $aQueryParams );
		}
		/**
		 * @param $aQueryParams
		 */
		public function redirectToAdmin( $aQueryParams = array() ) {
			$this->doRedirect( is_multisite()? get_admin_url() : admin_url(), $aQueryParams );
		}
		/**
		 * @param $aQueryParams
		 */
		public function redirectToHome( $aQueryParams = array() ) {
			$this->doRedirect( home_url(), $aQueryParams );
		}

		/**
		 * @param $sUrl
		 * @param array $aQueryParams
		 * @param bool $bSafe
		 */
		public function doRedirect( $sUrl, $aQueryParams = array(), $bSafe = true ) {
			$sUrl = empty( $aQueryParams ) ? $sUrl : add_query_arg( $aQueryParams, $sUrl );

			$oDp = $this->loadDataProcessor();
			// we prevent any repetitive redirect loops
			if ( $oDp->FetchCookie( 'icwp-isredirect' ) == 'yes' ) {
				return;
			}
			else {
				$oDp->setCookie( 'icwp-isredirect', 'yes', 7 );
			}

			// based on: https://make.wordpress.org/plugins/2015/04/20/fixing-add_query_arg-and-remove_query_arg-usage/
			// we now escape the URL to be absolutely sure since we can't guarantee the URL coming through there
			$sUrl = esc_url_raw( $sUrl );
			$bSafe ? wp_safe_redirect( $sUrl ) : wp_redirect( $sUrl );
			exit();
		}

		/**
		 * @return string
		 */
		public function getCurrentPage() {
			global $pagenow;
			return $pagenow;
		}

		/**
		 * @return WP_Post
		 */
		public function getCurrentPost() {
			global $post;
			return $post;
		}

		/**
		 * @return int
		 */
		public function getCurrentPostId() {
			$oPost = $this->getCurrentPost();
			return empty( $oPost->ID ) ? -1 : $oPost->ID;
		}

		/**
		 * @return string
		 */
		public function getUrl_CurrentAdminPage() {

			$sPage = $this->getCurrentPage();
			$sUrl = self_admin_url( $sPage );

			//special case for plugin admin pages.
			if ( $sPage == 'admin.php' ) {
				$sSubPage = $this->loadDataProcessor()->FetchGet( 'page' );
				if ( !empty( $sSubPage ) ) {
					$aQueryArgs = array(
						'page' 	=> $sSubPage,
					);
					$sUrl = add_query_arg( $aQueryArgs, $sUrl );
				}
			}
			return $sUrl;
		}

		/**
		 * @param string
		 * @return string
		 */
		public function getIsCurrentPage( $sPage ) {
			return $sPage == $this->getCurrentPage();
		}

		/**
		 * @param string
		 * @return string
		 */
		public function getIsPage_Updates() {
			return $this->getIsCurrentPage( 'update.php' );
		}

		/**
		 * @return bool
		 */
		public function getIsLoginRequest() {
			$oDp = $this->loadDataProcessor();
			return
				$oDp->GetIsRequestPost()
				&& !is_null( $oDp->FetchPost( 'log' ) )
				&& !is_null( $oDp->FetchPost( 'pwd' ) )
				&& $this->getIsLoginUrl();
		}

		/**
		 * @return bool
		 */
		public function getIsRegisterRequest() {
			$oDp = $this->loadDataProcessor();
			return
				$oDp->GetIsRequestPost()
				&& !is_null( $oDp->FetchPost( 'user_login' ) )
				&& !is_null( $oDp->FetchPost( 'user_email' ) )
				&& $this->getIsLoginUrl();
		}

		/**
		 * @return bool
		 */
		public function getIsLoginUrl() {
			$aLoginUrlParts = @parse_url( wp_login_url() );
			$aRequestParts = $this->loadDataProcessor()->getRequestUriParts();
			return ( !empty( $aRequestParts['path'] ) && ( rtrim( $aRequestParts['path'], '/' ) == rtrim( $aLoginUrlParts['path'], '/' ) ) );
		}

		/**
		 * @param $sTermSlug
		 * @return bool
		 */
		public function getDoesWpSlugExist( $sTermSlug ) {
			return ( $this->getDoesWpPostSlugExist( $sTermSlug ) || term_exists( $sTermSlug ) );
		}

		/**
		 * @param $sTermSlug
		 * @return bool
		 */
		public function getDoesWpPostSlugExist( $sTermSlug ) {
			$oDb = $this->loadDbProcessor();
			$sQuery = "
				SELECT ID
				FROM %s
				WHERE
					post_name = '%s'
					LIMIT 1
			";
			$sQuery = sprintf(
				$sQuery,
				$oDb->getTable_Posts(),
				$sTermSlug
			);
			$nResult = $oDb->getVar( $sQuery );
			return !is_null( $nResult ) && $nResult > 0;
		}

		/**
		 * @return bool
		 */
		public function getIsXmlrpc() {
			// XML-RPC Compatibility
			return ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST );
		}

		/**
		 * @return string
		 */
		public function getSiteName() {
			return function_exists( 'get_bloginfo' )? get_bloginfo('name') : 'WordPress Site';
		}
		/**
		 * @return string
		 */
		public function getSiteAdminEmail() {
			return function_exists( 'get_bloginfo' )? get_bloginfo('admin_email') : '';
		}

		/**
		 * @return string
		 */
		public function getCookieDomain() {
			return defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : false;
		}

		/**
		 * @return string
		 */
		public function getCookiePath() {
			return defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
		}

		/**
		 * @return boolean
		 */
		public function getIsAjax() {
			return defined( 'DOING_AJAX' ) && DOING_AJAX;
		}

		/**
		 * @return boolean
		 */
		public function getIsCron() {
			return defined( 'DOING_CRON' ) && DOING_CRON;
		}

		/**
		 * @param int $nId
		 * @return WP_User|null
		 */
		public function getUserById( $nId ) {
			if ( version_compare( $this->getWordpressVersion(), '2.8.0', '<' ) || !function_exists( 'get_user_by' ) ) {
				return null;
			}
			return get_user_by( 'id', $nId );
		}

		/**
		 * @param $sUsername
		 *
		 * @return bool|WP_User
		 */
		public function getUserByUsername( $sUsername ) {
			if ( empty( $sUsername ) ) {
				return false;
			}

			if ( version_compare( $this->getWordpressVersion(), '2.8.0', '<' ) ) {
				$oUser = get_userdatabylogin( $sUsername );
			}
			else {
				$oUser = get_user_by( 'login', $sUsername );
			}

			return $oUser;
		}

		/**
		 * @param array $aLoginUrlParams
		 */
		public function forceUserRelogin( $aLoginUrlParams = array() ) {
			$this->logoutUser();
			$this->redirectToLogin( $aLoginUrlParams );
		}

		/**
		 * @param string $sRedirectUrl
		 */
		public function logoutUser( $sRedirectUrl = '' ) {
			empty( $sRedirectUrl ) ? wp_logout() : wp_logout_url( $sRedirectUrl );
		}

		/**
		 * @return bool
		 */
		public function isMultisite() {
			if ( !isset( $this->bIsMultisite ) ) {
				$this->bIsMultisite = function_exists( 'is_multisite' ) && is_multisite();
			}
			return $this->bIsMultisite;
		}

		/**
		 * @param string $sKey
		 * @param string $sValue
		 * @return bool
		 */
		public function addOption( $sKey, $sValue ) {
			return $this->isMultisite() ? add_site_option( $sKey, $sValue ) : add_option( $sKey, $sValue );
		}

		/**
		 * @param string $sKey
		 * @param $sValue
		 * @return boolean
		 */
		public function updateOption( $sKey, $sValue ) {
			return $this->isMultisite() ? update_site_option( $sKey, $sValue ) : update_option( $sKey, $sValue );
		}

		/**
		 * @param string $sKey
		 * @param mixed $mDefault
		 * @return mixed
		 */
		public function getOption( $sKey, $mDefault = false ) {
			return $this->isMultisite() ? get_site_option( $sKey, $mDefault ) : get_option( $sKey, $mDefault );
		}

		/**
		 * @param string $sKey
		 * @return mixed
		 */
		public function deleteOption( $sKey ) {
			return $this->isMultisite() ? delete_site_option( $sKey ) : delete_option( $sKey );
		}

		/**
		 * @return string
		 */
		public function getCurrentWpAdminPage() {

			$oDp = $this->loadDataProcessor();
			$sScript = $oDp->FetchServer( 'SCRIPT_NAME' );
			if ( empty( $sScript ) ) {
				$sScript = $oDp->FetchServer( 'PHP_SELF' );
			}
			if ( is_admin() && !empty( $sScript ) && basename( $sScript ) == 'admin.php' ) {
				$sCurrentPage = $oDp->FetchGet( 'page' );
			}
			return empty( $sCurrentPage ) ? '' : $sCurrentPage;
		}

		/**
		 * @return null|WP_User
		 */
		public function getCurrentWpUser() {
			if ( is_user_logged_in() ) {
				$oUser = wp_get_current_user();
				if ( is_object( $oUser ) && $oUser instanceof WP_User ) {
					return $oUser;
				}
			}
			return null;
		}

		/**
		 * @return integer
		 */
		public function getCurrentUserLevel() {
			$oUser = $this->getCurrentWpUser();
			return ( is_object($oUser) && ($oUser instanceof WP_User) )? $oUser->get( 'user_level' ) : -1;
		}

		/**
		 * @param string $sUsername
		 *
		 * @return bool
		 */
		public function setUserLoggedIn( $sUsername ) {

			$oUser = $this->getUserByUsername( $sUsername );
			if ( !is_a( $oUser, 'WP_User' ) ) {
				return false;
			}

			wp_clear_auth_cookie();
			wp_set_current_user( $oUser->ID, $oUser->get( 'user_login' ) );
			wp_set_auth_cookie( $oUser->ID, true );
			do_action( 'wp_login', $oUser->get( 'user_login' ), $oUser );

			return true;
		}

		/**
		 * @param string $sKey should be already prefixed
		 * @param int|null $nId - if omitted get for current user
		 * @return bool|string
		 */
		public function getUserMeta( $sKey, $nId = null ) {
			$nUserId = $nId;
			if ( empty( $nUserId ) ) {
				$oCurrentUser = $this->getCurrentWpUser();
				if ( is_null( $oCurrentUser ) ) {
					return false;
				}
				$nUserId = $oCurrentUser->ID;
			}

			$sCurrentMetaValue = get_user_meta( $nUserId, $sKey, true );
			// A guard whereby if we can't ever get a value for this meta, it means we can never set it.
			if ( empty( $sCurrentMetaValue ) ) {
				//the value has never been set, or it's been installed for the first time.
				$this->updateUserMeta( $sKey, 'temp', $nUserId );
				return '';
			}
			return $sCurrentMetaValue;
		}

		/**
		 * @return false|WP_Automatic_Updater
		 */
		public function getWpAutomaticUpdater() {
			if ( !isset( $this->oWpAutomaticUpdater ) ) {
				if ( !class_exists( 'WP_Automatic_Updater', false ) ) {
					include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
				}
				if ( class_exists( 'WP_Automatic_Updater', false ) ) {
					$this->oWpAutomaticUpdater = new WP_Automatic_Updater();
				}
				else {
					$this->oWpAutomaticUpdater = false;
				}
			}
			return $this->oWpAutomaticUpdater;
		}

		/**
		 * Flushes the Rewrite rules and forces a re-commit to the .htaccess where applicable
		 */
		public function resavePermalinks() {
			/** @var WP_Rewrite $wp_rewrite */
			global $wp_rewrite;
			if ( is_object( $wp_rewrite ) ) {
				$wp_rewrite->flush_rules( true );
			}
		}

		/**
		 * Updates the user meta data for the current (or supplied user ID)
		 *
		 * @param string $sKey
		 * @param mixed $mValue
		 * @param integer $nId		-user ID
		 * @return boolean
		 */
		public function updateUserMeta( $sKey, $mValue, $nId = null ) {
			$nUserId = $nId;
			if ( empty( $nUserId ) ) {
				$oCurrentUser = $this->getCurrentWpUser();
				if ( is_null( $oCurrentUser ) ) {
					return false;
				}
				$nUserId = $oCurrentUser->ID;
			}
			return update_user_meta( $nUserId, $sKey, $mValue );
		}

		/**
		 * @return ICWP_WPSF_DataProcessor
		 */
		public function loadDataProcessor() {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-data.php' );
			return ICWP_WPSF_DataProcessor::GetInstance();
		}

		/**
		 * @return ICWP_WPSF_WpDb
		 */
		public function loadDbProcessor() {
			if ( !isset( self::$oWpDb ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-wpdb.php' );
				self::$oWpDb = ICWP_WPSF_WpDb::GetInstance();
			}
			return self::$oWpDb;
		}
	}
endif;

if ( !class_exists( 'ICWP_WPSF_WpFunctions', false ) ):

	class ICWP_WPSF_WpFunctions extends ICWP_WPSF_WpFunctions_V7 {
		/**
		 * @return ICWP_WPSF_WpFunctions
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}
	}
endif;