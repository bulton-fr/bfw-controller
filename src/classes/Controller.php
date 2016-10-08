<?php
/**
 * Classes géant les pages
 * @author Vermeulen Maxime <bulton.fr@gmail.com>
 * @version 1.0
 */

namespace BFWCtr;

/**
 * Permet de gérer la vue et de savoir vers quel page envoyer
 * @package bfw-controller
 */
class Controller implements \BFWCtrInterface\IController
{
    /**
     * @var $_kernel L'instance du Kernel
     */
    protected $_kernel;
    
    /**
     * @var $nameCtr Le nom du controler appelé
     */
    protected $nameCtr = '';
    
    /**
     * @var $nameMethode Le nom de la méthode à appeler
     */
    protected $nameMethode = '';
    
    /**
     * @var $link_file L'arborescence interne dans les fichiers
     */
    protected $fileArbo = '';
    
    /**
     * @var $arg Les arguments get
     */
    protected $arg = array();
    
    /**
     * @var $defaultPage La page par défault (celle qui sert de page index au site)
     */
    protected $defaultPage;
    
    /**
     * Constructeur
     * 
     * @param string $default_page (default: null) La page par défaut du site (la page index du site)
     */
    public function __construct($default_page=null)
    {
        $this->_kernel = getKernel();
        
        //Si la page par défaut a été indiqué, on la définie.
        if($default_page != null)
        {
            $this->setDefaultPage($default_page);
        }
        
        $this->arg2get(); //Découpe pour obtenir les gets
        $this->verifLink();
    }
    
    /**
     * Retourne l'arborescence vers le fichier controler (inclus)
     * 
     * @return string
     */
    public function getFileArbo()
    {
        if($this->fileArbo == '')
        {
            $this->decoupeLink();
        }
        
        return $this->fileArbo;
    }
    
    /**
     * Récupère les informatios mises en get
     */
    protected function arg2get()
    {
        if($this->fileArbo == '')
        {
            $this->decoupeLink();
        }
        
        global $_GET;
        $get_id = 0;
        
        foreach($this->arg as $val)
        {
            $_GET[$get_id] = secure(trim($val));
            $get_id += 1;
        }
    }
    
    /**
     * On vérifie le lien pour trouver le controler
     */ 
    protected function verifLink()
    {
        if($this->fileArbo == '')
        {
            $this->decoupeLink();
        }
        
        global $_GET;
        
        //Si le fichier a ouvrir est indiqué dans la variable get 'page_uri' et s'il existe, on l'ouvre.
        if(isset($_GET['page_uri']))
        {
            global $path;
            $page_uri = secure(trim($_GET['page_uri']));
            
            if(file_exists($path.'controlers/'.$page_uri.'.php'))
            {
                $this->fileArbo = $page_uri;
            }
        }
    }
    
    /**
     * Récupère le lien de la page
     * 
     * @return void
     */
    protected function decoupeLink()
    {
        //Link de la forme : /compte/user/xx/yy avec le dossier compte, la page user et 2 valeurs get (xx et yy)
        
        global $_GET, $path, $base_url;
        $link      = $_SERVER['REQUEST_URI']; //On récupère l'url courante
        $exBaseUrl = explode('/', $base_url); //Découpe le base url
        
        if(count($exBaseUrl) > 3)
        {
            unset($exBaseUrl[0], $exBaseUrl[1], $exBaseUrl[2]);
            $imBaseUrl = '/'.implode('/', $exBaseUrl);
            $lenBaseUrl = strlen($imBaseUrl);
            
            $link = substr($link, $lenBaseUrl);
        }
        
        //S'il s'agit de la page index ou racine, on envoi vers la page par défault
        if($link == '/index.php' || $link == '/')
        {
            $this->fileArbo = $this->defaultPage;
            $this->nameCtr = $this->defaultPage;
            
            return;
        }
        
        $link = substr($link, 1); //enlève le premier / de l'url
        $exArg = explode('?', $link);
        $ex = explode('/', $exArg[0]); //Découpage de l'url, on découpe sur chaque /
        
        $file_find = false; //Indique si le fichier a été trouvé
        $dir_find = false; //Indique si le dossier a été trouvé
        
        $dirArbo = '';
        $methode = '';
        
        foreach($ex as $val)
        {
            //Le fichier à été trouvé
            if($file_find)
            {
                $this->arg[] = $val;
                continue;
            }
            
            //Tant qu'on a pas trouvé le fichier
            
            if(!empty($dirArbo) && empty($methode)) {$methode = $val;}
            
            //On rajoute un / à la fin du lien si on a commencé à mettre des choses dessus
            if($this->fileArbo != '') {$this->fileArbo .= '/';}
            
            $this->fileArbo .= $val; //Et on y rajoute la valeur lu
            
            //Si le fichier existe dans le dossier modèle. On passe la $file_find à true
            if(file_exists(path_controllers.'/'.$this->fileArbo.'.php'))
            {
                $this->nameCtr = $this->fileArbo;
                $file_find = true;
            }
            
            //Si un dossier existe pourtant le nom, on passe $dir_find à true
            if(file_exists(path_controllers.'/'.$this->fileArbo))
            {
                $dir_find = true;
                $dirArbo = $this->fileArbo;
            }
        }
        
        if($file_find == true) {return;}
        
        //Si rien a été trouvé, on rajoute "/index" à la fin du lien
        if($dir_find == true)
        {
            global $ctr_class;
            
            $this->nameCtr     = $this->fileArbo;
            $this->nameMethode = $methode;
            
            if(!(method_exists('\controller\\'.$dirArbo, $methode) && $ctr_class))
            {
                $this->fileArbo = $dirArbo.'/index';
                $this->nameCtr = $dirArbo.'\index';
            }
        }
        else
        {
            global $ctr_defaultMethode;
            $this->nameMethode = $ctr_defaultMethode;
            
            if(isset($ex[0])) {$this->nameMethode = $ex[0];}
        }
    }
    
    /**
     * Modifie la page par défault
     * 
     * @param string $name Le nom de la page index du site
     */
    public function setDefaultPage($name)
    {
        $this->defaultPage = $name;
        $this->decoupeLink();
    }
    
    /**
     * Retourne le nom du controler utilisé
     * 
     * @return string
     */
    public function getNameCtr()
    {
        if($this->fileArbo == '')
        {
            $this->decoupeLink();
        }
        
        return str_replace('/', '\\', $this->nameCtr);
    }
    
    /**
     * Retourne la méthode à appeler
     * 
     * @return string
     */
    public function getMethode()
    {
        if($this->fileArbo == '')
        {
            $this->decoupeLink();
        }
        
        if(empty($this->nameMethode))
        {
            if(isset($this->arg[0]))
            {
                $this->nameMethode = $this->arg[0];
                unset($this->arg[0]);
            }
            else
            {
                if(empty($ctr_defaultMethode))
                {
                    global $ctr_defaultMethode;
                    $this->nameMethode = $ctr_defaultMethode;
                }
                else
                {
                    $this->nameMethode = 'index';
                }
            }
        }
        
        return $this->nameMethode;
    }
}