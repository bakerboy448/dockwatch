<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Image
{
    public function removeImage($image)
    {
        $cmd = sprintf(DockerSock::REMOVE_IMAGE, $image);
        return shell_exec($cmd . ' 2>&1');
    }
}