<?php

/**
 * Authentification to admin panel
 * @package PLX
 * @author	Stephane F, Florent MONTHEL, Pedro "P3ter" CADETE
 **/

namespace controllers;

use models\PlxConfigModel;

class IndexController {

    const PLX_ROOT_DIR = '../';
    const PLX_ADMIN_DIR = 'admin/';
    const PLX_VIEWS_COMMON_DIR = 'admin/views/common/';
    const PLX_VIEWS_LAYOUTS_DIR = 'admin/views/layouts/';
    const PLX_VIEWS_SCRIPTS_DIR = 'admin/views/scripts/';
    
    private $_rootDir = Self::PLX_ROOT_DIR;
    private $_adminDir = Self::PLX_ROOT_DIR . Self::PLX_ADMIN_DIR;
    private $_viewsCommonDir = Self::PLX_ROOT_DIR . Self::PLX_VIEWS_COMMON_DIR;
    private $_viewsLayoutDir = Self::PLX_ROOT_DIR . Self::PLX_VIEWS_LAYOUTS_DIR;
    private $_viewsScriptsDir = Self::PLX_ROOT_DIR . Self::PLX_VIEWS_SCRIPTS_DIR;
    private $_authPage = false;
    private $_configIni = array(); //array from config.ini parsing
    
    public function __construct(){
        session_start();
        
        //config.ini parsing to an array
        $plxConfig = new PlxConfigModel();

    	// Checking PluXml installation before continue
        if(!file_exists($plxConfig->getConfig('XMLFILE_PARAMETERS'))) {
    	    header('Location: ' . $this->_adminDir . 'install.php');
    	    exit;
    	}
    	
    	if($this->_authPage !== true){ # si on est pas sur la page de login
    	    # Test sur le domaine et sur l'identification
    	    if((isset($_SESSION['domain']) AND $_SESSION['domain']!=$session_domain) OR (!isset($_SESSION['user']) OR $_SESSION['user']=='')){
    	        header('Location: auth.php?p='.htmlentities($_SERVER['REQUEST_URI']));
    	        exit;
    	    }
    	}
    }
    
    /**
     * Get $_rootDir
     * @return string
     * @author Pedro "P3ter" CADETE
     */
    public function getRootDir(){
        return $this->_rootDir;
    }
    
    /**
     * Get $_adminDir
     * @return string
     * @author Pedro "P3ter" CADETE
     */
    public function getAdminDir(){
        return $this->_adminDir;
    }
    
    /**
     * Get $_viewsCommonDir
     * @return string
     * @author Pedro "P3ter" CADETE
     */
    public function getViewsCommonDir(){
        return $this->_viewsCommonDir;
    }
    
    /**
     * Get $_viewsLayoutDir
     * @return string
     */
    public function getViewsLayoutDir(){
        return $this->_viewsLayoutDir;
    }
    
    /**
     * Get $_viewsScriptsDir
     * @return string
     * @author Pedro "P3ter" CADETE
     */
    public function getViewsScriptsDir(){
        return $this->_viewsScriptsDir;
    }
    
    /**
     * Get $_authPage
     * @return boolean
     * @author Pedro "P3ter" CADETE
     */
    public function getAuthPage() {
        return $this->_authPage;
    }
    
    /**
     * Get $_configIni array from config.ini parsing
     * @return array
     * @author Pedro "P3ter" CADETE
     */
    public function getConfigIni() {
        return  $this->_configIni;
    }
    
    /**
     * Set $_authPage
     * Used for identify the authentification page to block PluXml backoffice access
     * @param $value boolean    true if the current page is the authentification page
     * @return boolean
     * @author Pedro "P3ter" CADETE
     */
    public function setAuthPage($value) {
        return $this->_authPage = $value;
    }
}
?>
