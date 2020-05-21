<!DOCTYPE html>
<html>
    <head>
        <meta charset="windows-1252">
        <title>Link Analysis</title>
    </head>

    <body>
    <center>
        <h2 style="color:#000080">Link Analysis</h2>   
        <form method="POST" >
            #Iterations: <br>
            <input type="radio" name="iteration" id="auto" onclick="document.getElementById('k').disabled = true;
                    document.getElementById('i').disabled = true;"value="auto" checked="">Auto (iterations will be stop at zero difference)
            <br>
            <input type="radio" name="iteration" id="manual" onclick="document.getElementById('k').disabled = false;
                    document.getElementById('k').value = '20';
                    document.getElementById('i').disabled = false;" value="manual">Manual 
            <input  type="text" name="k" id="k" disabled="">
            <br><br>
            <input type="checkbox" name="i" id="i" value="i" disabled="">Without Normalization
            <br>
            <input type="submit" style="width:200px" name="all" value="Display All Iterations">
            <input type="submit" style="width:200px" name="final" value="Display Final Result">
            <br><br>
            <input type="submit" style="width:120px" name="a" value="Rank Authorities">
            <input type="submit" style="width:120px" name="h" value="Rank Hubs">
        </form>
    </center>
    <?php
    /*
    this program uses HITS Algorithm to apply link analysis locally
    documents should be in a folder named "documents"

    program handling ..
    -Empty files
    -out links
    -case insensitive of links
    -optional normalization
    -iterations:auto,manual
    -deletion of folder "documents"

    */
    if (isset($_POST["all"]) || isset($_POST["final"]) || isset($_POST["a"]) || isset($_POST["h"])) {
        if (!is_dir("documents"))
            die("<br><center>There is no directory with name \"documents\"<br>we check only files into it</center>");
        $files = scandir("documents");
        $files = filesHandling($files);
        $files = outLinks($files);
        $A = initial_values($files);
        $A = fill_data($A);
        $At = transpose($A);
        $h = initialization($files);
        $a = initialization($files);
        if ($_POST["iteration"] == "manual") {
            $k = $_POST["k"];
            $s = "manual";
        } else {
            $k = 1000;
            $s = "auto";
        }
        $res = iterations($A, $At, $h, $a, $k, $s);
        if (isset($_POST["final"]))
            printData($res["a"], $res["h"]);
        if (isset($_POST["a"])) {
            rank($res["a"], "Authorities");
        }
        if (isset($_POST["h"])) {
            rank($res["h"], "Hubs");
        }
    }

    function filesHandling($files) {
        unset($files[0]);
        unset($files[1]);
        foreach ($files as $key => $value) {
            $files[$key] = strrev(substr(strrev($value), 4));
            $files[$key] = strtolower($files[$key]);
        }
        return $files;
    }

    function outLinks($files) {
        $string = " ";
        foreach ($files as $i) {
            $f = fopen("documents/" . $i . ".txt", "r");
            if (filesize("documents/" . $i . ".txt") > 0)
                $string = $string . " " . strtolower(fread($f, filesize("documents/" . $i . ".txt")));
        }
        $string = $string . " " . implode(" ", $files);
        $string = preg_split("/[\s]+/", trim($string));
        $files = array_unique($string);
        return $files;
    }

    function initial_values($files) {
        foreach ($files as $i) {
            foreach ($files as $j) {
                $A[$i][$j] = 0;
            }
        }
        return $A;
    }

    function fill_data($A) {
        foreach ($A as $i => $v) {
            if (file_exists("documents/" . $i . ".txt")) {
                $f = fopen("documents/" . $i . ".txt", "r");
                if (filesize("documents/" . $i . ".txt") > 0)
                    $string = strtolower(fread($f, filesize("documents/" . $i . ".txt")));
                else
                    $string = " ";
                foreach ($A [$i] as $j => $value) {
                    if ($i != $j) {
                        if (substr_count($string, $j) > 0) {
                            $A[$i][$j] = 1;
                        }
                    }
                }
            }
        }
        return $A;
    }

    function initialization($files) {
        foreach ($files as $value) {
            $h[$value] = 1;
        }
        return $h;
    }

    function transpose($A) {
        foreach ($A as $i => $v) {
            foreach ($A as $j => $value) {
                $At[$j][$i] = $A[$i][$j];
            }
        }
        return $At;
    }

    function iterations($A, $At, $h, $a, $k, $s) {
        for ($i = 0; $i < $k; $i++) {
            $anew = mult($At, $h);
            $hnew = mult($A, $a);
            if ($s == "auto")
                if (difference($a, normalization($anew)) && difference($h, normalization($hnew)))
                    break;
            if (!isset($_POST["i"])) {
                $a = normalization($anew);
                $h = normalization($hnew);
            } else {
                $a = $anew;
                $h = $hnew;
            }
            if (isset($_POST["all"])) {
                echo"<br><center><b>iteration number " . ($i + 1) . "</b></center>";
                echo"<center><table width='50%' border='1'><th>File</th><th>Authority</th><th>Hub</th>";
                foreach ($a as $key => $value) {
                    echo"<tr><td><center>$key</center></td><td><center>$value</center></td><td><center>$h[$key]</center></td></tr>";
                }
                echo"</table></center>";
            }
        }
        $res["a"] = $a;
        $res["h"] = $h;
        return $res;
    }

    function difference($a, $b) {
        $flag = 1;
        foreach ($a as $key => $value) {
            if (abs($a[$key] - $b[$key]) >= 0.00000000000001) {
                $flag = 0;
                break;
            }
        }
        return $flag;
    }

    function mult($matrix, $vector) {
        foreach ($matrix as $i => $v1) {
            $res[$i] = 0;
            foreach ($matrix[$i] as $j => $v2) {
                $res[$i]+=$matrix[$i][$j] * $vector [$j];
            }
        }
        return $res;
    }

    function normalization($arr) {
        $sum = 0;
        foreach ($arr as $value) {
            $sum+=($value * $value );
        }
        $c = sqrt($sum);
        foreach ($arr as $key => $value) {
            $arr[$key] = $arr[$key] / $c;
        }
        return $arr;
    }

    function printData($a, $h) {
        echo"<br><center><table width='50%' border='1'><th>File</th><th>Authority</th><th>Hub</th>";
        foreach ($a as $key => $value) {
            echo"<tr><td><center>$key</center></td><td><center>$value</center></td><td><center>$h[$key]</center></td></tr>";
        }
        echo"</table></center>";
    }

    function rank($arr, $name) {
        $arr = array_diff($arr, array("0"));
        if (count($arr) == 0) {
            echo"<center><p color=#2471a3>there are no $names</p></center>";
        } else {
            arsort($arr);
            echo"<br>";
            foreach ($arr as $key => $value) {
                $link = "documents/" . $key . ".txt";
                echo "<center><a href=$link>$key</a></center>";
            }
        }
    }
    ?>
</body>

</html>
