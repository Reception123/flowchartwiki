<?php

//////////////////////////////////////////////////////////////
//
//    Copyright (C) Thomas Kock, Delmenhorst, 2008, 2009
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License along
// with this program; if not, write to the Free Software Foundation, Inc.,
// 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
// http://www.gnu.org/copyleft/gpl.html
//
//////////////////////////////////////////////////////////////

class CheckFchw extends SpecialPage {
    function CheckFchw() {
        //SpecialPage::SpecialPage("CheckFchw");
        parent::__construct("CheckFchw");
        // wfLoadExtensionMessages('checkfchw'); // removed in MW-1.21.1
    }

    function execute( $par ) {
        global $wgRequest, $wgOut, $wgScriptPath, $fchw, $dbr, $wgVersion, $IP, $wgDBprefix;
        global $wgUploadDirectory;
        clearstatcache();
        $dbr = wfGetDB( DB_SLAVE );
        $this->setHeaders();

        # Get request data from, e.g.
        $param = $wgRequest->getText('param');

        function Status($Ok, $Text, $Hint) {
            //<strong>".(($Ok) ? "ok" : "failed")."</strong>
            return "<tr><td style='padding-left: 4px; padding-right: 16px; background: ".(($Ok) ? "green" : "red").";'><strong>".(($Ok) ? "OK" : "ERROR")."</strong></td><td valign='top' style='padding: 8px; background: ".(($Ok) ? "green" : "red").";'><strong>$Text</strong><br /><font color='white'>".(($Ok) ? "" : $Hint)."</font></td></tr>";
        }

        # Output
        $output = "";
        $output .= "<p>Web Server: <strong>".$_SERVER["SERVER_SOFTWARE"]."</strong></p>";
        $output .= "<p>PHP version: <strong>".phpversion()."</strong></p>";
        $output .= "<p>Platform: <strong>".php_uname()."</strong></p>";
        $output .= "<p>Mediawiki version: <strong>".$wgVersion."</strong></p>";
        $output .= "<p>Database: <strong>".$dbr->getSoftwareLink()." ".$dbr->getServerVersion()."</strong></p>";
        $output .= "<p>Database prefix: <strong>".$wgDBprefix."</strong></p>";
        $output .= "<p>FlowChartWiki version: <strong>".fchw_version."</strong></p>";
        $output .= "<table border='0' cellpadding='0' cellspacing='0'><tr><td>";

        $output .= "<table border='0' cellpadding='0' cellspacing='0'>";
        $dir = dirname(dirname(dirname(__FILE__))) . '/images/flowchartwiki';
        $dircheck = file_exists($dir);
        $output .= Status($dircheck, wfMessage('checkfchwFolderCreated'), wfMessage('checkfchwFolderCreatedHint'));

        $dirtouch = false;
        if ($dircheck) {
            $dirtouch = (is_writable($dir));
        }
        $output .= Status($dirtouch, wfMessage('checkfchwFolderPermissions'), wfMesssge('checkfchwFolderPermissionsHint'));

        $graphvizpath = isset( $fchw['GraphvizDot']);
        $output .= Status($graphvizpath, wfMessage('checkfchwGraphVizPath'), wfMessage('checkfchwGraphVizPathHint'));

        $graphvizexec = false;
        if ($graphvizpath) {
            $graphvizexec = is_executable( $fchw['GraphvizDot']);
        }
        $output .= Status($graphvizexec, wfMessage('checkfchwGraphVizExec'), wfMessage('checkfchwGraphVizExecHint'));

        $fchwdb = false;
        $dbr = &wfGetDB(DB_MASTER);
        try {
            $dbr->query("select * from ".$wgDBprefix."fchw_relation where from_id=0");
            $fchwdb = true;
        } catch (Exception $e) {
            $fchwdb = false;
        }
        $output .= Status($fchwdb, wfMessage('checkfchwDbTables'), wfMessage('checkfchwDbTablesHint'));

        // REAL GRAPHVIZ TEST
        $execTest = false;
        $execTestMessage = wfMessage('checkfchwGraphVizExecTestHint1');
        if (($dirtouch) && ($dircheck) && ($graphvizpath) && ($graphvizexec) && ($fchwdb)) {
            $execTestMessage = wfMessage('checkfchwGraphVizExecTestHint2');
            global $fchw, $wgUploadPath, $wgUploadDirectory;
            $ImgDirWeb  = "/flowchartwiki/";
            $ImgDir  = str_replace('/', DIRECTORY_SEPARATOR, $ImgDirWeb);
            $DotDir  = str_replace('/', DIRECTORY_SEPARATOR, $fchw['GraphvizDot']);
            $DataDir = str_replace('/', DIRECTORY_SEPARATOR, $wgUploadDirectory.$ImgDir);
            $Filename = "FchwTest";
            $PNGFile = $DataDir."$Filename.png";
            $PNGPath = $wgUploadPath.$ImgDirWeb."$Filename.png";
            $DOTFile = $DataDir."$Filename.dot";

            //$DataDir should exist due to tests above.
            //if (!is_dir($DataDir))
            //mkdir($DataDir, 0777);
            if (file_exists($DOTFile))
            unlink($DOTFile);
            if (file_exists($PNGFile))
            unlink($PNGFile);

            $gd = fopen($DOTFile, "w");
            fwrite($gd, "
digraph G { size =\"7,2.4\"; { rank = same; \"Flightbooking\";  }
{ rank = same; \"ValueStream\";  }
{ rank = same; \"GettingStarted\";  }
{ rank = same; \"ShapeTest\";  }
\"Flightbooking\" [URL=\"/wiki/index.php/Category:Flightbooking\",shape=rect,color=yellow, fontcolor=black, style=filled,];
\"Flightbooking\" [URL=\"/wiki/index.php/Category:Flightbooking\",];
\"GettingStarted\" [URL=\"/wiki/index.php/Category:GettingStarted\",shape=rect,color=firebrick2, fontcolor=black, style=filled,];
\"GettingStarted\" [URL=\"/wiki/index.php/Category:GettingStarted\",];
\"ShapeTest\" [URL=\"/wiki/index.php/Category:ShapeTest\",shape=rect,color=forestgreen, fontcolor=black, style=filled,];
\"ShapeTest\" [URL=\"/wiki/index.php/Category:ShapeTest\",];
\"ValueStream\" [URL=\"/wiki/index.php/Category:ValueStream\",shape=box,color=blue, fontcolor=black, style=filled,];
\"ValueStream\" [URL=\"/wiki/index.php/Category:ValueStream\",];
\"Flightbooking\"->\"ValueStream\" [  ];
\"Flightbooking\"->\"ValueStream\" [  ];
\"GettingStarted\"->\"ShapeTest\" [  ];
\"GettingStarted\"->\"ShapeTest\" [  ];
\"ValueStream\"->\"GettingStarted\" [  ];
}

        ");
            fclose($gd);

            $exec = escapeshellarg($DotDir)." -Tpng -o ".escapeshellarg($PNGFile)." ".escapeshellarg($DOTFile);
            //$exec = escapeshellarg($DotDir)." -Tpng -o ".$PNGFile." ".$DOTFile;
            if (substr(php_uname(), 0, 7) == "Windows") {
                try {
                    $obj = new COM("WScript.Shell") or die("Unable to init WScript.Shell for Png file");
                    // for Apache / PHP
                    if (substr($_SERVER["SERVER_SOFTWARE"],0,6) == "Apache") {
                        $Res = $obj->Run("".$exec, 0, true);
                    } else {
                        // for IIS
                        $Res = $obj->Exec("".$exec);
                    }
                    $obj = null;
                    // On Windows, PHP sometimes does not immediately recognize that the file is there.
                    // http://de.php.net/manual/en/function.file-exists.php#56121
                    $start = gettimeofday();
                    while (!file_exists($PNGFile)) {
                        $stop = gettimeofday();
                        if ( 1000000 * ($stop['sec'] - $start['sec']) + $stop['usec'] - $start['usec'] > 500000) break;  // wait a moment
                    }
                } catch (Exception $e ) {
                    $execTestMessage = "<b>Command:</b><br>".$exec."<br><b>Error:</b><br>".$e->__toString();
                }
            } else {
                exec($exec);
            }
            $execTest = file_exists($PNGFile);
        }
        $output .= Status($execTest, wfMessage('checkfchwGraphVizExecTest'), $execTestMessage );

        // FINAL STATUS
        $output .= "<tr><td colspan='2' style='height: 2px'>&nbsp;</td></tr>";
        $output .= Status(($dirtouch) && ($dircheck) && ($graphvizpath) && ($graphvizexec) && ($fchwdb) && ($execTest), wfMessage('checkfchwTotal'), "");
        $output .= "</table>";

        $output .= "</td><td style='padding-left: 32px;'>";

        if ($execTest) {
            $output .= "<h5>Test-Graphics</h5><img src='$PNGPath?TimeStamp=".date("YmdHis")."' alt='' />";
        }

        $output .= "</td></tr></table>";

        $wgOut->addHTML( $output );
    }
}
