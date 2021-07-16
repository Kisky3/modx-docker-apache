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
 * File Name: GetFolders.php
 * 	Implements the GetFolders command, to list the folders
 * 	in the current directory. Output is in XML
 *
 * File Authors:
 * 		Grant French (grant@mcpuk.net)
 */
class GetFolders {
    public $fckphp_config;
    public $type;
    public $cwd;
    public $actual_cwd;

    function __construct($fckphp_config,$type,$cwd) {
        $this->fckphp_config=$fckphp_config;
        $this->type=$type;
        $this->raw_cwd=$cwd;
		$this->actual_cwd=str_replace('//','/',($fckphp_config['UserFilesPath']."/{$type}/{$cwd}"));
		$this->real_cwd=str_replace('//','/',($this->fckphp_config['basedir'].'/'.$this->actual_cwd));
	}
	
	function run() {
		header ("content-type: text/xml");
		echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		?>
<Connector command="GetFolders" resourceType="<?php echo $this->type; ?>">
	<CurrentFolder path="<?php echo $this->raw_cwd; ?>" url="<?php echo $this->actual_cwd; ?>" />
	<Folders>
<?php
		/**
		 * Initiate the array to store the filenames
		 */
		$files_in_folder = array();
		
		$files = scandir($this->real_cwd);
		if($files)
		{
		    foreach ($files as $filename)
		    {
				if (($filename!='.')&&($filename!='..'))
				{
					if (is_dir($this->real_cwd . $filename))
					{
						//check if$fckphp_configured not to show this folder
						$hide=false;
						for($i=0;$i<sizeof($this->fckphp_config['ResourceAreas'][$this->type]['HideFolders']);$i++)
						{
							$pattern = $this->fckphp_config['ResourceAreas'][$this->type]['HideFolders'][$i];
							$hide=(preg_match("/{$pattern}/",$filename) ? true : $hide);
						}
						/**
						* Dont echo the entry, push it in the array
						*/
						if (!$hide) array_push($files_in_folder,$filename);
					}
				}
			}
		}
		/**
		 * Sort the array by the way you like and show it.
		 */
		natcasesort($files_in_folder);
        foreach($files_in_folder as $k=>$v)
        {
           echo '<Folder name="'.$v.'" />'."\n";
        }
?>
	</Folders>
</Connector>
		<?php
	}
}
