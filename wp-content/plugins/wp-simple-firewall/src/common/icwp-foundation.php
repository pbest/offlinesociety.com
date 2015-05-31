<?php

if ( !class_exists( 'ICWP_WPSF_Foundation', false ) ) :

	class ICWP_WPSF_Foundation {

		/**
		 * @var ICWP_WPSF_DataProcessor
		 */
		private static $oDp;
		/**
		 * @var ICWP_WPSF_WpFilesystem
		 */
		private static $oFs;
		/**
		 * @var ICWP_WPSF_WpFilesystem
		 */
		private static $oWp;
		/**
		 * @var ICWP_WPSF_Render
		 */
		private static $oRender;
		/**
		 * @var ICWP_WPSF_YamlProcessor
		 */
		private static $oYaml;

		/**
		 * @return ICWP_WPSF_DataProcessor
		 */
		static public function loadDataProcessor() {
			if ( !isset( self::$oDp ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-data.php' );
				self::$oDp = ICWP_WPSF_DataProcessor::GetInstance();
			}
			return self::$oDp;
		}

		/**
		 * @return ICWP_WPSF_WpFilesystem
		 */
		static public function loadFileSystemProcessor() {
			if ( !isset( self::$oFs ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-wpfilesystem.php' );
				self::$oFs = ICWP_WPSF_WpFilesystem::GetInstance();
			}
			return self::$oFs;
		}

		/**
		 * @return ICWP_WPSF_WpFunctions
		 */
		static public function loadWpFunctionsProcessor() {
			if ( !isset( self::$oWp ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-wpfunctions.php' );
				self::$oWp = ICWP_WPSF_WpFunctions::GetInstance();
			}
			return self::$oWp;
		}

		/**
		 * @return ICWP_WPSF_WpDb
		 */
		static public function loadDbProcessor() {
			return self::loadWpFunctionsProcessor()->loadDbProcessor();
		}

		/**
		 * @param string $sTemplatePath
		 * @return ICWP_WPSF_Render
		 */
		static public function loadRenderer( $sTemplatePath = '' ) {
			if ( !isset( self::$oRender ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-render.php' );
				self::$oRender = ICWP_WPSF_Render::GetInstance()
					->setAutoloaderPath( dirname( __FILE__ ) . ICWP_DS . 'Twig' . ICWP_DS . 'Autoloader.php' );
			}
			if ( !empty( $sTemplatePath ) ) {
				self::$oRender->setTemplatePath( $sTemplatePath );
			}
			return self::$oRender;
		}

		/**
		 * @return ICWP_WPSF_YamlProcessor
		 */
		static public function loadYamlProcessor() {
			if ( !isset( self::$oYaml ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-yaml.php' );
				self::$oYaml = ICWP_WPSF_YamlProcessor::GetInstance();
			}
			return self::$oYaml;
		}

		/**
		 * @return ICWP_Stats_APP
		 */
		public function loadStatsProcessor() {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-stats.php' );
		}
	}

endif;