<?php
/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2005 Frederico Caldeira Knabben
 *
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 *
 * For further information visit:
 * 		http://www.fckeditor.net/
 *
 * File Name: DeleteFolder.php
 * 	Implements the DeleteFolder command to delete a folder
 * 	in the current directory. Output is in XML.
 *
 * File Authors:
 * 		Grant French (grant@mcpuk.net)
 */
class DeleteFolder {
    public $fckphp_config;
    public $type;
    public $cwd;
    public $actual_cwd;

	
	function __construct($fckphp_config,$type,$cwd) {
		$this->fckphp_config=$fckphp_config;
		$this->type=$type;
		$this->raw_cwd=$cwd;
		$this->actual_cwd=str_replace("//","/",($this->fckphp_config['UserFilesPath']."/$type/".$this->raw_cwd));
		$this->real_cwd=str_replace("//","/",($this->fckphp_config['basedir']."/".$this->actual_cwd));
		$this->foldername=str_replace(array("..","/"),"",$_GET['FolderName']);
	}
	
	function run() {
		
		if ($this->delDir($this->real_cwd.'/'.$this->foldername)) $err_no=0;
		else                                                      $err_no=402;
		
		header ("content-type: text/xml");
		echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		?>
<Connector command="DeleteFolder" resourceType="<?php echo $this->type; ?>">
	<CurrentFolder path="<?php echo $this->raw_cwd; ?>" url="<?php echo $this->actual_cwd; ?>" />
	<Error number="<?php echo "".$err_no; ?>" />
</Connector>
		<?php
	}
	
	function delDir($dir)
	{
		$files = scandir($dir);
		if ($files)
		{
			foreach($files as $entry)
			{
				if (($entry!=".")&&($entry!=".."))
				{
					if (is_dir($dir.'/'.$entry))
					{
						$this->delDir($dir.'/'.$entry);
					}
					else
					{
						$thumb=$dir.'/.thumb/'.$entry;
						if (is_file($thumb))
						{
							if (!unlink($thumb)) return false;
						}
						if (!unlink($dir.'/'.$entry)) return false;
					}
				}
			}
			return rmdir($dir);
		}
		else
		{
			return false;
		}
	}
}
