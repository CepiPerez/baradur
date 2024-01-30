<?php

Class PDF 
{
    protected $html;
    protected $file;
    protected $options = array();

    public function __construct($html=null, $file=null)
    {
        $this->html = $html;
        $this->file = $file;
    }

    private function generate($filename, $overwrite = true)
    {
        $folder = _DIR_ . config('pdf.path') . '/';
        $folder = str_replace('//', '/', $folder);

        if (substr(strtolower($filename),-4)!='.pdf') {
            $filename .= '.pdf';
        }

        if (file_exists($folder.$filename) && !$overwrite) {
            if ($overwrite) {
                @unlink($folder.$filename);
            } else {
                return;
            }
        }

        $command = config('pdf.bin').' ';
        
        foreach ($this->options as $key => $value) {
            $command .= '-- ' . strtolower($key) . ' '. $value . ' ';
        }

        if ($this->html) {
            @unlink($folder.'temp_file.html');
            file_put_contents($folder.'temp_file.html', $this->html);
            chmod($folder.'temp_file.html', 0777);
            
            $command .= $folder . 'temp_file.html ' . $folder.$filename;
        } else {
            $command .= $this->file . ' ' . $folder.$filename;
        }
                
        shell_exec($command);

        @unlink($folder.'temp_file.html');

        if (!file_exists($folder.$filename)) {
            throw new Exception("Error creating PDF file. Check binary configuration.");
        }

        chmod($folder.$filename, 0777);

        return $folder.$filename;
    }

    public static function loadView($template, $params)
    {
        $content = View::loadTemplate($template, $params);
        return new PDF($content, null);
    }

    public static function loadHtml($html)
    {
        return new PDF($html, null);
    }

    public static function loadFile($file)
    {
        return new PDF(null, $file);
    }


    public function setOrientation($orientation)
    {
        $this->options['orientation'] = $orientation;
        return $this;
    }

    public function setPaper($paper, $orientation=null)
    {
        $this->setOption('page-size', $paper);
        if ($orientation) {
            $this->setOption('orientation', $orientation);
        }
        return $this;
    }

    public function setOption($option, $value) {
        $this->options[$option] = $value;
        return $this;
    }

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
        return $this;
    }

    public function output()
	{
		if ($this->html || $this->file) {
			return file_get_contents($this->generate('temp.pdf', true));
		}

		throw new InvalidArgumentException('PDF Generator requires a html or file in order to produce output.');
    }

    public function download($filename)
    {
        $res = $this->generate($filename, true);

        $headers = array();
        $header['content-Transfer-Encoding'] = 'binary';
        $header['Accept-Ranges'] = 'bytes';

        return response()->download($res, $filename.'.pdf', $headers);
    }

    public function inline($filename)
    {
        $res = $this->generate($filename, true);

        $headers = array();
        $header['content-Transfer-Encoding'] = 'binary';
        $header['Accept-Ranges'] = 'bytes';

        return response()->file($res, $headers);
    }

    public function save($filename, $overwrite = false)
    {   
        if ($this->html || $this->file) {
            $this->generate($filename, $overwrite);
        }

        return $this;
    }

}