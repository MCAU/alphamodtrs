<?php
/* 
    Basic ModTRS Web interface
    Copyright (C) 2011 Alphabeat (c/o xrobau@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    Configuration:
        Set 'db' to be the location of your ModTRS.db file
        Set 'bg' to be your background. 
        Set 'css' to be your stylesheet
        
*/

#$db = "sqlite:/data/minecraft/1.2_untitled/plugins/ModTRS/ModTRS.db";
$db = "sqlite:/data/minecraft/1.2_untitled/plugins/ReportRTS/ReportRTS.db";
$bg = "http://mcau.org/wp-content/uploads/2011/10/minecraft-wallpaper-151.jpg";
$css = "http://mcau.org/wp-content/themes/twentyeleven/style.css";

function time_elapsed($ptime) {
    $etime = time() - $ptime;
    
    if ($etime < 1) {
        return '0 seconds';
    }
    
    $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                7 * 24 * 60 * 60        =>  'week',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hour',
                60                      =>  'minute',
                1                       =>  'second'
                );
    
    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '');
        }
    }
}

$fullpath = $_SERVER["PHP_SELF"];
$parts = Explode('/', $fullpath);
$sn = $parts[count($parts) - 1];
if (isset($_REQUEST['source']) && $_REQUEST['source'] === "true") {
    highlight_file($sn);
    exit;
}
?>

<head>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo $css ?>" />
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<title>Minecraft Australia Tickets</title>
<style type="text/css">
body { background-image: url("<?php echo $bg ?>"); background-repeat: repeat; background-position: top left; background-attachment: fixed; }
#tickets {margin:0 20px 0 20px;}
#page {max-width:1200px !important;}
table.grid {width:100%;}
table.grid td { border-bottom:1px solid #EEE;background:#EEE;padding:5px; }
table.grid tr.even td {background:#FAFAFA !important;}
table.grid th {font-weight:bold;background:#E8E8E8; padding:10px;}
</style>
</head>
<body>
<div id="page">
<div id="tickets">
<h2 class="entry-title">Minecraft Australia Ticketing system</h2>
<div id="chart_div"></div>
<h3>Most active mod: </h3><span id="activemod" style="height:20px;width:65px;"></span>
<h3>Latest 100 tickets</h3>
<?php
$statii = array(0 => "New", 1 => "In Progress", 2 => "Unknown", 3=> "Done");
$dbh = new PDO($db);
$query = "select r.id,r.mod_comment as comment, u.name as username,DATETIME(r.tstamp+ (10*60*60), 'unixepoch') as created,r.text as request,mu.name as mod, r.status from reportrts_request r join reportrts_user u on r.user_id=u.id left join reportrts_user mu on r.mod_id = mu.id order by r.id desc limit 100";

    echo "<table class='grid'>";
    echo "<thead>";
    echo "<tr><th>ID</th><th>Username</th><th>Requested</th><th>Assigned To</th><th>Request</th><th>Status</th></tr>";
    echo "</thead><tbody>";
    $i = 0;
    foreach($dbh->query($query) as $row){
        $i++;
        $class = $i % 2 ? "even" : "odd";
        $txtclass = $row['status'] == 1 ? "orangeRed" : "gray";
        echo "<tr class='".$class."'>";
        print "<td>".$row['id']."</td>";
        print "<td>".$row['username']."</td>";
        print "<td><a onclick='return false;' href='#' title='".$row['created']."'>".time_elapsed(strtotime($row['created']))."</a></td>";
        print "<td>".$row['mod']."</td>";
        print "<td>".htmlspecialchars($row['request'])."</td>";
        print "<td><text style='color:".$txtclass."'>".htmlspecialchars($statii[$row['status']])."</text></td>";

        echo "</tr>\n";
        if ($row['comment'] != '') {
            print "<tr class='".$class."'><td colspan=3></td><td><b>Mod Comment:</b></td><td colspan=2>".$row['comment']."</td></tr>";
        }
    }
    echo "</tbody>";
    echo "</table>";
?>

<br />
<? 
   /*
   This next section of code is an easy way for you to remain compliant with the AGPL.
   Remove it, and you'll need to find another way to provide people with the source.
   */
?>
<table style="border:2px solid black" width=100%>
    <tr>
        <td>This program is licenced under the AGPL V3. The source of this script
        <a href="<?php echo "$sn?source=true" ?>">is automatically generated through this link.</a></td>
        <td><img style="vertical-align:middle;" src="http://www.gnu.org/graphics/agplv3-88x31.png"></td>
    </tr>
</table>
</div> <!--content-->
</div> <!--page-->
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart","imagesparkline"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
    var data = new google.visualization.DataTable();
    data.addColumn('date','Date');
    data.addColumn('number','Requests');
    <?php
        $query = "select count(*) as c, date(DATETIME(tstamp  + (10*60*60), 'unixepoch')) as t  from reportrts_request where t > date('now','-7 days') group by t";
        foreach($dbh->query($query) as $row){
           list($y,$m,$d) = split('-',$row['t']);
           print "data.addRow([new Date(".$y.",".$m.",".$d."),".$row['c']."]);\n"; 

        }

    print "var chart = new google.visualization.LineChart(document.getElementById('chart_div'));\n";
    print "chart.draw(data,{legend:'none',height:240,title:'Requests per day'});\n";

    print "var moddata = new google.visualization.DataTable();\n";


    $sql = "select r.mod_id,u.name from reportrts_request r join reportrts_user u on r.mod_id = u.id where date(DATETIME(tstamp + (10*60*60), 'unixepoch')) > date('now','-7 days') group by r.mod_id, u.name order by count(*) desc limit 1;";
    $sth = $dbh->prepare($sql);
    $sth->execute();
    $result = $sth->fetch();
    $activemodid = $result[0];
    $activemodname = $result[1];
    $sql = "select count(*) as c, date(DATETIME(tstamp + (10*60*60), 'unixepoch')) as t  from reportrts_request where t > date('now','-7 days') and mod_id = 1 group by t;";
    $activemodreqs = $dbh->query($sql);
    print "moddata.addColumn('number','".$activemodname."');\n";
    print "moddata.addRows(7);\n";
    $c = 0;
    foreach($dbh->query($query) as $row){
        print "moddata.setValue(".$c.",0,".$row['c'].");\n";
        $c++;
    }
    print "var activemodspark = new google.visualization.ImageSparkLine(document.getElementById('activemod'));\n";
    print "activemodspark.draw(moddata, {width: 65, height: 20, showAxisLines: false,  showValueLabels: false, labelPosition: 'left'});\n";

?>

}

</script>
</body>
