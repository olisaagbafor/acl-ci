<?php

class Acl extends MY_Controller {

    public function __construct() {
        parent::__construct();
        //$this->load->library('acl');
    }

    public function index() {
        exit(__FUNCTION__);
        $this->acl->read();
        $r = new ReflectionClass($class);
        $doc = $r->getDocComment();
        preg_match_all('#@(.*?)\n#s', $doc, $annotations);
        return $annotations[1];
    }

    public function fetch() {
        $this->listFolderFiles();
    }

    public function listFolderFiles($dir = null) {
        if ($dir === null)
            $dir = constant('APPPATH') . 'controllers/';
        
        $ffs = scandir($dir);

        unset($ffs[0], $ffs[1]);
        // prevent empty ordered elements
        if (count($ffs) < 1)
            return;

        $i = 0;
        
        foreach ($ffs as $ff) {

            if (is_dir($dir . '/' . $ff))
                $this->listFolderFiles($dir . '/' . $ff);
            elseif (is_file($dir . '/' . $ff) && strpos($ff,'.php') !== false) {
                $classes = $this->get_php_classes(file_get_contents($dir . '/' . $ff));
                include_once($dir.'/'.$ff);
                foreach($classes AS $class){
                    $methods = $this->get_class_methods($class, true);
                    //$this->AclModel->checkInsert($class, $method);
                }
            }
            if ($i > 5)
                break;
            else
                $i++;
        }
        
    }

    public function get_php_classes($php_code, $methods = false) {
        $classes = array();
        $tokens = token_get_all($php_code);

        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) { 
                $classes[] = $tokens[$i][1]; // assigning class name to classes array variable
                
            }
        }
        return $classes;
    }

    public function get_class_methods($class, $comment = false){
        $r = new ReflectionClass($class);
        
        foreach($r->getMethods() AS $m){
            if($m->class == $class){
                $arr = ['name'=>$m->name];
                if($comment === true){
                    $arr['docComment'] = $this->get_method_comment($r, $m->name);
                }
                $methods[] = $arr;
            }
                
        }
        
        return $methods;
    }
    
    public function get_method_comment($obj,$method){
        $comment = $obj->getMethod($method)->getDocComment();
        //define the regular expression pattern to use for string matching
        $pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";

        //perform the regular expression on the string provided
        preg_match_all($pattern, $comment, $matches, PREG_PATTERN_ORDER);
        $comments = [];
        foreach($matches[0] as $match){
            $comments[] = preg_split('/[\s]/',$match, 2);
        }
        
        return $comments;
    }
}
