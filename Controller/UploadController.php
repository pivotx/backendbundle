<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\MinLength;
use Symfony\Component\Validator\Constraints\Collection as Collection;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class UploadController extends Controller
{
    protected function handleFileUpload($file)
    {
        $info = array();

        $info['valid']    = false;
        $info['name']     = $file->getClientOriginalName();
        $info['mimetype'] = $file->getClientMimeType();
        $info['size']     = $file->getClientSize();
        $info['message']  = 'Something unknown went wrong.';

        //echo '<pre>'; var_dump($file); echo '</pre>';

        $directory = getcwd().'/data/upload-quarantaine';
        if (!is_dir($directory)) {
            @mkdir($directory, 0777);
            @chown($directory, 0777);
        }

        if (!is_dir($directory)) {
            // @todo error!!
            $info['message'] = 'Could not create upload directory.';
            return $info;
        }

        $cnt = 0;
        do {
            $cnt++;

            $name = date('YmdHis').'-'.mt_rand(1000,9999).'.dat';
            if (file_exists($directory.'/'.$name)) {
                $name = null;
                break;
            }
        }
        while (($cnt < 100) && (is_null($name)));

        if (is_null($name)) {
            // @todo error
            $info['message'] = 'Could not generate storage file.';
            return $info;
        }


        if ($file->isValid()) {
            $moved_file = null;
            try {
                $moved_file = $file->move($directory, $name);
            }
            catch (FileException $e) {
                $moved_file = null;
            }
            if (is_null($moved_file)) {
                $info['message'] = 'Could not move file to quarantaine location.';
                return $info;
            }

            $info['valid']     = true;
            $info['tmp_name']  = $name;
            $info['message']   = '';
            //$info['file']  = $moved_file;
        }

        return $info;
    }

    protected function handlePost($files)
    {
        $info = array();

        //echo '<pre>'; var_dump($files); echo '</pre>';

        if (is_array($files)) {
            foreach($files as $file) {
                $info[] = $this->handleFileUpload($file);
            }
        }
        else {
            $info[] = $this->handleFileUpload($files);
        }

        return $info;
    }

    public function processUploadAction(Request $request)
    {
        $info = array();

        //echo '<pre>'; var_dump($request->files->get('form')); echo '</pre>';

        switch ($request->getMethod()) {
            case 'POST':
                //var_dump($request->files->all());
                foreach($request->files->all() as $name => $file) {
                    $info = $this->handlePost($file);
                }
                /*
                $form = $request->files->get('form');
                $file = $form['filename'];
                $info = $this->handlePost($file);
                 */
                break;
        }

        $content = json_encode($info);

        return new \Symfony\Component\HttpFoundation\Response($content, 200);
    }
}
