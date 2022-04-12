<?php

/**
 * BladeOne - A Blade Template implementation in a single file
 * Copyright (c) 2016 Jorge Patricio Castro Castillo MIT License. Don't delete this comment, its part of the license.
 * Part of this code is based in the work of Laravel PHP Components.
 *
 *
 */

/**
 * Class BladeOne
 * @package  BladeOne
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.3 2016-06-12
 * @link https://github.com/EFTEC/BladeOne
 */

#namespace eftec\bladeone;

#use Exception;

class BladeOne
{

    protected $extensions = array();
    protected $sections = array();
    protected $fileName;
    protected $sectionStack = array();
    protected $loopsStack = array();
    protected $variables=array();
    protected $pushStack = array();
    protected $pushes = array();
    protected $renderCount = 0;
    protected $templatePath;
    protected $compiledPath;
    protected $customDirectives = array();
    protected $path;
    protected $isForced=false;
    protected $isRunFast=false;
    protected $rawTags = array('{!!', '!!}');
    protected $ukmethods = array();
    protected $contentTags = array('{{', '}}');
    protected $escapedTags = array('{{{', '}}}');
    protected $echoFormat = '(%s)';
    protected $footer = array();
    protected $verbatimPlaceholder = '@__verbatim__@';
    protected $verbatimBlocks = array();
    protected $forelseCounter = 0;
    protected $compilers = array(
        'Extensions',
        'Statements',
        'Comments',
        'Echos',
    );

    public $phpTag='<?php ';


    public function __construct($templatePath, $compiledPath)
    {
        $this->templatePath = $templatePath;
        $this->compiledPath = $compiledPath;
    }
 
    public function runChild($view,$variables=array()) {

        $newVariables=array_merge($variables,$this->variables);
        return $this->runInternal($view,$newVariables,$this->isForced,false,$this->isRunFast);
    }


    public function run($view,$variables=array())
    {
        $mode=0; // mode=0 automatic: not forced and not run fast.
        if (defined('BLADEONE_MODE')) {
            $mode=BLADEONE_MODE;
        }
        $forced = $mode & 1; // mode=1 forced:it recompiles no matter if the compiled file exists or not.
        $runFast = $mode & 2; // mode=2 runfast: the code is not compiled neither checked and it runs directly the compiled

        if ($mode==3) {
            $this->showError("run","we can't force and run fast at the same time",true);
        }
        return $this->runInternal($view,$variables,$forced,true,$runFast);
    }


    private function runInternal($view,$variables=array(), $forced=false,$isParent=true,$runFast=false)
    {
 
        if ($isParent) {
            $this->variables=$variables;
        }
        if (!$runFast) {
            // a) if the compile is forced then we compile the original file, then save the file.
            // b) if the compile is not forced then we read the datetime of both file and we compared.
            // c) in both cases, if the compiled doesn't exist then we compile.
            $this->compile($view, $forced);
        } else {
            // running fast, we don't compile neither we check or read the original template.
            if ($view) {
                $this->fileName = $view;
            }
        }
        $this->isForced=$forced;
        $this->isRunFast=$runFast;
        return $this->evaluatePath($this->getCompiledFile(),$variables);
    }


    public function compile($fileName = null,$forced=false)
    {
        if ($fileName) {
            $this->fileName = $fileName;
        }
        $compiled = $this->getCompiledFile();
        $template=$this->getTemplateFile();
        if ($this->isExpired() || $forced) {
            // compile the original file
            $contents = $this->compileString($this->getFile($template));

            /* foreach(preg_split("/((\r?\n)|(\r\n?))/", $contents) as $line){
                echo "L:: <code>".$line."</code><br>";
            } */

            if (!is_null($this->compiledPath)) {
                file_put_contents($compiled, $contents);
            }
        }
    }


    public function compileString($value)
    {
        $result = '';

        if (strpos($value, '@verbatim') !== false) {
            $value = $this->storeVerbatimBlocks($value);
        }

        $this->footer = array();

        // Here we will loop through all of the tokens returned by the Zend lexer and
        // parse each one into the corresponding valid PHP. We will then have this
        // template as the correctly rendered PHP that can be rendered natively.

        /* $res = array();
        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        } */

        foreach ($this->compilers as $type) {
            $value = $this->{"compile{$type}"}($value);
        }
        $result = '<?php global $errors; ?>'.$value;


        if (! empty($this->verbatimBlocks)) {
            $result = $this->restoreVerbatimBlocks($result);
        }

        // If there are any footer lines that need to get added to a template we will
        // add them here at the end of the template. This gets used mainly for the
        // template inheritance via the extends keyword that should be appended.
        if (count($this->footer) > 0) {
            $result = ltrim($result, PHP_EOL)
                .PHP_EOL.implode(PHP_EOL, array_reverse($this->footer));
        }

        return $result;
    }

    protected function compileExtensions($value)
    {
        foreach ($this->extensions as $compiler) {
            echo "<hr><hr>extensions $compiler<hr><hr>";
            $value = call_user_func($compiler, $value, $this);
        }

        return $value;
    }

    protected function compileComments($value)
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', $this->contentTags[0], $this->contentTags[1]);

        return preg_replace($pattern, $this->phpTag.'/*$1*/ ?>', $value);
    }


    protected function compileEchos($value)
    {
        foreach ($this->getEchoMethods() as $method => $length) {
            $value = $this->$method($value);
        }

        return $value;
    }

    protected function compileMethod($expression)
    {
        $v = $this->stripParentheses($expression);

        return "<input type='hidden' name='_method' value='{$this->phpTag}echo $v; " . "?>'/>";
    }

    protected function compileError($expression)
    {
        /* return $this->phpTag."if ( App::getError{$expression} ): ?>"; */
        return $this->phpTag.' $__errorMsg = App::getError('.$expression.');
        if ($__errorMsg) :
        if (isset($message)) $__messageOriginal = $message;
        $message = $__errorMsg; ?>';    
    }

    protected function compileEnderror()
    {
        return '<?php unset($message);
        if (isset($__messageOriginal)) { $message = $__messageOriginal; }
        endif;
        unset($__errorMsg); ?>';
    }

    protected function compileCsrf()
    {
        $csrf = App::generateToken();
        $template = '<input type="hidden" id="csrf" name="csrf" value="'.$csrf.'">';
        return "<input type='hidden' name='csrf' value='".$csrf."'/>";
    }

    
    function callbackCompileStatements($match) {
        //echo "REPLACING: ";var_dump($match);echo "<br>";
        if ($this->contains($match[1], '@')) {
            $match[0] = isset($match[3]) ? $match[1].$match[3] : $match[1];
        } elseif (isset($this->customDirectives[$match[1]])) {
            $match[0] = call_user_func($this->customDirectives[$match[1]], $this->get($match, 3));
        } elseif (method_exists($this, $method = 'compile'.ucfirst($match[1]))) {
            /* echo "COMPILING ".ucfirst($match[1])." :: ".$this->get($match, 2)
            ." :: ".$this->get($match, 3)
            ." :: ".$this->get($match, 4)."<br>"; */
            $match[0] = $this->$method($this->get($match, 3));
        } else {
            $this->showError("@compile","Operation not defined:@".$match[1],true);
        }

        return isset($match[3]) ? $match[0] : $match[0].$match[2];
    }

    protected function compileStatements($value)
    {
        //echo "STATEMENT: ".$value."<br>";
        return preg_replace_callback('/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', 
            array($this, 'callbackCompileStatements'),
            $value);
    }

    function callbackCompileRawEchos($matches) {
        //echo "REPLACING: ";var_dump($matches);echo "<br>";
        $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

        return $matches[1] ? substr($matches[0], 1) : $this->phpTag.'echo '.$this->compileEchoDefaults($matches[2]).'; ?>'.$whitespace;
    }

    protected function compileRawEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

        return preg_replace_callback($pattern, array($this, "callbackCompileRawEchos"), $value);
    }

    function callbackCompileRegularEchos($matches) {
        
        $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];
        
        $wrapped = sprintf($this->echoFormat, $this->compileEchoDefaults($matches[2]));
        
        /* $wrapped = str_replace('asset(', 'View::getAsset(', $wrapped);
        $wrapped = str_replace('route(', 'Route::getRoute(', $wrapped);
        $wrapped = str_replace('session(', 'App::getSession(', $wrapped); */

        return $matches[1] ? substr($matches[0], 1) : $this->phpTag.'echo '.$wrapped.'; ?>'.$whitespace;
    }

    protected function compileRegularEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

        return preg_replace_callback($pattern, array($this, "callbackCompileRegularEchos"), $value);
    }

    function callbackCompileEscapedEchos($matches) {
        $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

        return $matches[1] ? $matches[0] : $this->phpTag.'echo ('.$this->compileEchoDefaults($matches[2]).'); ?>'.$whitespace;
    }

    protected function compileEscapedEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        return preg_replace_callback($pattern, array($this, "callbackCompileEscapedEchos"), $value);
    }

    public function compileEchoDefaults($value)
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    protected function compileEach($expression)
    {
        return $this->phpTag."echo \$this->renderEach{$expression}; ?>";
    }

    protected function compileInject($expression)
    {
        $segments = explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression));

        return $this->phpTag.'$'.trim($segments[0])." = app('".trim($segments[1])."'); ?>";
    }

    protected function compileYield($expression)
    {
        return $this->phpTag."echo \$this->yieldContent{$expression}; ?>";
    }


    function generateCallTrace()
    {
        /* $e = new Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $result = array();

        for ($i = 0; $i < $length; $i++)
        {
            $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
        }

        return "\t" . implode("\n\t", $result); */
    }

    protected function compileShow()
    {
        return $this->phpTag.'echo $this->yieldSection(); ?>';
    }

    protected function compileSection($expression)
    {
        return $this->phpTag."\$this->startSection{$expression}; ?>";
    }

    protected function compileAppend()
    {
        return $this->phpTag.'$this->appendSection(); ?>';
    }

    protected function compileEndsection()
    {
        return $this->phpTag.'$this->stopSection(); ?>';
    }

    protected function compileStop()
    {
        return $this->phpTag.'$this->stopSection(); ?>';
    }

    protected function compileOverwrite()
    {
        return $this->phpTag.'$this->stopSection(true); ?>';
    }

    protected function compileUnless($expression)
    {
        return $this->phpTag."if ( ! $expression): ?>";
    }

    protected function compileEndunless()
    {
        return $this->phpTag.'endif; ?>';
    }

    protected function compileLang($expression)
    {
        /* return $this->phpTag."echo app('translator')->get$expression; ?>"; */
        return $this->phpTag."echo App::trans$expression; ?>";
    }

    protected function compileChoice($expression)
    {
        return $this->phpTag."echo App::choice$expression; ?>";
    }

    protected function compileElse()
    {
        return $this->phpTag.'else: ?>';
    }

    protected function compileFor($expression)
    {
        return $this->phpTag."for{$expression}: ?>";
    }

    protected function compileForeach($expression)
    {
        preg_match('/\( *(.*) * as *([^\)]*)/', $expression, $matches);

        $iteratee = trim($matches[1]);

        $iteration = trim($matches[2]);

        $initLoop = "\$__currentLoopData = {$iteratee}; \$this->addLoop(\$__currentLoopData);";

        $iterateLoop = '$this->incrementLoopIndices(); $loop = $this->getFirstLoop();';

        return $this->phpTag."{$initLoop} foreach(\$__currentLoopData as {$iteration}): {$iterateLoop} ?>";
    }

    protected function compileBreak($expression)
    {
        return $expression ? $this->phpTag."if{$expression} break; ?>" : $this->phpTag.'break; ?>';
    }

    protected function compileContinue($expression)
    {
        return $expression ? $this->phpTag."if{$expression} continue; ?>" : $this->phpTag.'continue; ?>';
    }

    protected function compileForelse($expression)
    {
        $empty = '$__empty_'.++$this->forelseCounter;

        return $this->phpTag."{$empty} = true; foreach{$expression}: {$empty} = false; ?>";
    }

    protected function compileCan($expression)
    {
        return $this->phpTag."if ( Gate::allows{$expression} ): ?>";
    }

    protected function compileElsecan($expression)
    {
        return $this->phpTag."elseif ( Gate::allows{$expression} ): ?>";
    }

    protected function compileCannot($expression)
    {
        return $this->phpTag."if ( Gate::denies{$expression} ): ?>";
    }

    protected function compileElsecannot($expression)
    {
        return $this->phpTag."elseif ( Gate::denies{$expression} ): ?>";
    }

    protected function compileIf($expression)
    {
        return $this->phpTag."if{$expression}: ?>";
    }

    protected function compileElseif($expression)
    {
        return $this->phpTag."elseif{$expression}: ?>";
    }

    protected function compileEmpty()
    {
        $empty = '$__empty_'.$this->forelseCounter--;

        return $this->phpTag."endforeach; if ({$empty}): ?>";
    }

    protected function compileHasSection($expression)
    {
        return $this->phpTag."if (! empty(trim(\$this->yieldContent{$expression}))): ?>";
    }

    protected function compileEndwhile()
    {
        return $this->phpTag.'endwhile; ?>';
    }

    protected function compileEndfor()
    {
        return $this->phpTag.'endfor; ?>';
    }

    protected function compileEndforeach()
    {
        return $this->phpTag.'endforeach; $this->popLoop(); $loop = $this->getFirstLoop(); ?>';
    }

    protected function compileEndcan()
    {
        return $this->phpTag.'endif; ?>';
    }

    protected function compileEndcannot()
    {
        return $this->phpTag.'endif; ?>';
    }

    protected function compileEndif()
    {
        return $this->phpTag.'endif; ?>';
    }

    protected function compileEndforelse()
    {
        return $this->phpTag.'endif; ?>';
    }

    protected function compilePhp($expression)
    {
        return $expression ? $this->phpTag."{$expression}; ?>" : $this->phpTag.'';
    }

    protected function compileEndphp()
    {
        return ' ?>';
    }

    protected function compileUnset($expression)
    {
        return $this->phpTag."unset{$expression}; ?>";
    }

    protected function compileExtends($expression)
    {
        $expression = $this->stripParentheses($expression);
        /*
        $data = $this->phpTag."echo \$__env->make($expression, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
        */
        $data= $this->phpTag.'echo $this->runChild('.$expression.'); ?>';
        $this->footer[] = $data;

        return '';
    }

    protected function compileInclude($expression)
    {
        $expression = $this->stripParentheses($expression);

        return $replace = $this->phpTag.'echo $this->runChild('.$expression.'); ?>';
        /* return $this->phpTag."echo \$__env->make($expression, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
        */
    }

    protected function compileIncludeIf($expression)
    {
        $expression = $this->stripParentheses($expression);

        return $replace = $this->phpTag.'if (\$this->exists($expression)) echo $this->runChild('.$expression.'); ?>';

        /*return $this->phpTag."if (\$__env->exists($expression)) echo \$__env->make($expression, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
        */
    }

    protected function compileStack($expression)
    {
        return $this->phpTag."echo \$this->yieldPushContent{$expression}; ?>";
    }

    protected function compilePush($expression)
    {
        return $this->phpTag."\$this->startPush{$expression}; ?>";
    }

    protected function compileEndpush()
    {
        return $this->phpTag.'$this->stopPush(); ?>';
    }

 
    public function startPush($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            $this->extendPush($section, $content);
        }
    }

    public function stopPush()
    {
        if (empty($this->pushStack)) {
            $this->showError('stopPush','Cannot end a section without first starting one',true);
        }

        $last = array_pop($this->pushStack);
        $this->extendPush($last, ob_get_clean());
        return $last;
    }

    protected function extendPush($section, $content)
    {
        if (! isset($this->pushes[$section])) {
            $this->pushes[$section] = array();
        }
        if (! isset($this->pushes[$section][$this->renderCount])) {
            $this->pushes[$section][$this->renderCount] = $content;
        } else {
            $this->pushes[$section][$this->renderCount] .= $content;
        }
    }

    public function yieldPushContent($section, $default = '')
    {
        if (! isset($this->pushes[$section])) {
            return $default;
        }

        return implode(array_reverse($this->pushes[$section]));
    }


    function callbackStoreVerbatimBlocks ($matches) {
        $this->verbatimBlocks[] = $matches[1];

        return $this->verbatimPlaceholder;
    }

    protected function storeVerbatimBlocks($value)
    {
        return preg_replace_callback('/(?<!@)@verbatim(.*?)@endverbatim/s', array($this, "callbackStoreVerbatimBlocks"), $value);
    }

    protected function convertArg($array,$merge=null) {
        if (!is_array($array)) {
            if ($array=='') {
                return '';
            }
            // if its text then its converted to an array ['index'=>value,'index2'=>value]..
            $regexp = "@(\S+)=(\"|'| |)(.*)(\"|'| |>)@isU";
            preg_match_all($regexp, "<TAG ".$array, $p);
            $array=array_combine($p[1],$p[3]);
            // $array=array_change_key_case(array_combine($p[1],$p[3]),CASE_LOWER);
        }
        if ($merge!=null) {
            $array=array_merge($array,$merge);
        }
        return implode(' ',array_map( 'convertArgCallBack', array_keys($array), $array));

    }
    function convertArgCallBack($k, $v) {
        return $k."='{$v}' ";
    }

    function callbackRestoreVerbatimBlocks() {
        return array_shift($this->verbatimBlocks);
    }

    protected function restoreVerbatimBlocks($result)
    {
        $result = preg_replace_callback('/'.preg_quote($this->verbatimPlaceholder).'/', 
                    array($this, "callbackRestoreVerbatimBlocks"), $result);

        $this->verbatimBlocks = array();

        return $result;
    }

    protected function parseToken($token)
    {
        list($id, $content) = $token;

        /* echo "<br> TOKEN <br>";
        echo $content;
        echo "<br> TOKEN <br>"; */


        if ($id == T_INLINE_HTML) {
            foreach ($this->compilers as $type) {
                $content = $this->{"compile{$type}"}($content);
            }
        }

        return $content;
    }

    function _uksort($method1, $method2) {
        // Ensure the longest tags are processed first


        if ($this->ukmethods[$method1] > $this->ukmethods[$method2]) {
            return -1;
        }
        if ($this->ukmethods[$method1] < $this->ukmethods[$method2]) {
            return 1;
        }

        // Otherwise give preference to raw tags (assuming they've overridden)
        if ($method1 === 'compileRawEchos') {
            return -1;
        }
        if ($method2 === 'compileRawEchos') {
            return 1;
        }

        if ($method1 === 'compileEscapedEchos') {
            return -1;
        }
        if ($method2 === 'compileEscapedEchos') {
            return 1;
        }
        
        //throw new Exception('Method not defined');
    }

    protected function getEchoMethods()
    {
        $this->ukmethods = array(
            'compileRawEchos' => strlen(stripcslashes($this->rawTags[0])),
            'compileEscapedEchos' => strlen(stripcslashes($this->escapedTags[0])),
            'compileRegularEchos' => strlen(stripcslashes($this->contentTags[0])),
        );

        uksort($this->ukmethods, array($this, "_uksort"));

        return $this->ukmethods;
    }


    public function yieldSection()
    {
        return $this->yieldContent($this->stopSection());
    }

    public function startSection($section, $content = '')
    {
        if ($content === '')
        {
            ob_start() && $this->sectionStack[] = $section;
        }
        else
        {
            $this->extendSection($section, $content);
        }
    }

    protected function extendSection($section, $content)
    {
        if (isset($this->sections[$section]))
        {
            $content = str_replace('@parent', $content, $this->sections[$section]);

            $this->sections[$section] = $content;
        }
        else
        {
            $this->sections[$section] = $content;
        }
    }

    public function stopSection($overwrite = false)
    {
        $last = array_pop($this->sectionStack);

        if ($overwrite)
        {
            $this->sections[$last] = ob_get_clean();
        }
        else
        {
            $this->extendSection($last, ob_get_clean());
        }

        return $last;
    }

    public function yieldContent($section, $default = '')
    {
        return isset($this->sections[$section]) ? $this->sections[$section] : $default;
    }

    protected function compileWhile($expression)
    {
        return $this->phpTag."while{$expression}: ?>";
    }


    protected function stripParentheses($expression)
    {
        if ($this->startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }

    public function extend(callable $compiler)
    {
        $this->extensions[] = $compiler;
    }

    public function directive($name, callable $handler)
    {
        $this->customDirectives[$name] = $handler;
    }

    public function setContentTags($openTag, $closeTag, $escaped = false)
    {
        $property = ($escaped === true) ? 'escapedTags' : 'contentTags';

        $this->{$property} = array(preg_quote($openTag), preg_quote($closeTag));
    }

    public function setEscapedContentTags($openTag, $closeTag)
    {
        $this->setContentTags($openTag, $closeTag, true);
    }

    public function getContentTags()
    {
        return $this->getTags();
    }

    public function getEscapedContentTags()
    {
        return $this->getTags(true);
    }

    protected function getTags($escaped = false)
    {
        $tags = $escaped ? $this->escapedTags : $this->contentTags;

        return array_map('stripcslashes', $tags);
    }

    public function getCompiledFile() {
        return $this->compiledPath.'/'.sha1($this->fileName);
    }

    public function getTemplateFile() {
        $arr=explode('.',$this->fileName);
        $c=count($arr);
        if ($c==1) {
            return $this->templatePath . '/' . $this->fileName . '.blade.php';
        } else {
            $file=$arr[$c-1];
            array_splice($arr,$c-1,$c-1); // delete the last element
            $path=implode('/',$arr);
            return $this->templatePath . '/' .$path.'/'. $file . '.blade.php';
        }
    }

    public function isExpired()
    {
        $compiled = $this->getCompiledFile();
        $template=$this->getTemplateFile();

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        if ( ! $this->compiledPath || ! file_exists($compiled))
        {
            return true;
        }
        return filemtime($compiled) < filemtime($template);
    }

    public function getFile($fileName)
    {
        if (is_file($fileName)) {
            /* $res = file_get_contents($fileName);
            $res = preg_replace('/(@end[a-zA-Z0-9]+)/s', " $1 ", $res);
            $res = preg_replace('/(@cs[a-zA-Z0-9]+)/s', " $1 ", $res);
            return $res; */
            return file_get_contents($fileName);

        }
        $this->showError('getFile',"File does not exist at path {$fileName}",true);
        return '';
    }

    protected function evaluatePath($compiledFile, $variables)
    {
        ob_start();

        extract($variables);

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try
        {
            /** @noinspection PhpIncludeInspection */
            include $compiledFile;
        }
        catch (\Exception $e)
        {
            $this->handleViewException($e);
        }

        return ltrim(ob_get_clean());
    }


    protected function handleViewException($e)
    {  
        ob_get_clean(); throw $e;
    }


    public function get($array, $key, $default = null)
    {
        $accesible=is_array($array) || $array instanceof ArrayAccess;
        if (! $accesible) {
            return self::value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if ($this->exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if ($accesible && $this->exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return self::value($default);
            }
        }

        return $array;
    }

    public function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    public function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? self::value($default) : reset($array);
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                return $value;
            }
        }

        return self::value($default);
    }

    public function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? $this->value($default) : end($array);
        }

        return $this->first(array_reverse($array), $callback, $default);
    }

    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    public function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    public function e($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
 
    public function addLoop($data)
    {
        $length = is_array($data) || $data instanceof Countable ? count($data) : null;

        $parent = $this->last($this->loopsStack);

        $this->loopsStack[] = array(
            'index' => 0,
            'remaining' => isset($length) ? $length + 1 : null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length == 1 : null,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $parent ? (object) $parent : null,
        );
    }

    public function incrementLoopIndices()
    {
        $loop = &$this->loopsStack[count($this->loopsStack) - 1];

        $loop['index']++;

        $loop['first'] = $loop['index'] == 1;

        if (isset($loop['count'])) {
            $loop['remaining']--;

            $loop['last'] = $loop['index'] == $loop['count'];
        }
    }


    public function popLoop()
    {
        array_pop($this->loopsStack);
    }

    public function getFirstLoop()
    {
        return ($last = $this->last($this->loopsStack)) ? (object) $last : null;
    }

    public function getLoopStack()
    {
        return $this->loopsStack;
    }


    public function renderEach($view, $data, $iterator, $empty = 'raw|')
    {
        $result = '';

        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial view to the final result HTML passing in the
        // iterated value of this data array, allowing the views to access them.
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $data = array('key' => $key, $iterator => $value);

                $result .= $this->runChild($view,$data);
            }
        }

        // If there is no data in the array, we will render the contents of the empty
        // view. Alternatively, the "empty view" could be a raw string that begins
        // with "raw|" for convenience and to let this know that it is a string.
        else {
            //todo: pendiente
            if ($this->startsWith($empty, 'raw|')) {
                $result = substr($empty, 4);
            } else {
                $result = $this->run($empty,array());
            }
        }

        return $result;
    }

    public function showError($id,$text,$critic=false) {
        ob_get_clean();
        echo "<div style='background-color: red; color: black; padding: 3px; border: solid 1px black;'>";
        echo "BladeOne Error [{$id}]:<br>";
        echo "<span style='color:white'>$text</span><br></div>\n";
        if ($critic) {
            die(1);
        }
        return "";
    }

}