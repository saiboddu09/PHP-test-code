@extends('layouts.advertiser')

@section('content')

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<?php

		// Get day
function get_day($date){
	$timestr = strtotime($date);
	$day = getdate($timestr)['mday'];
	return $day;
}
		// Get month
function get_month($date = NULL){
	if($date){
		$timestr = strtotime($date);
		$month = getdate($timestr)['mon'];
	}else{
		$month = getdate()['mon'];
	}
	return $month;
}
		// Get week name
function get_week($date){
	$timestr = strtotime($date);
	$week_name = getdate($timestr)['weekday'];
	return $week_name;
}

$d1 = '';
$d2 = '';
$w1 = '';
$w2 = '';
$m1 = '';
if(isset($_GET['v'])){
	switch($_GET['v']){
		case '3':
		$d1 = 'selected';
		break;
		case '5':
		$d2 = 'selected';
		break;
		case '7':
		$w1 = 'selected';
		break;
		case '14':
		$w2 = 'selected';
		break;
		default:
		$m1 = 'selected';
	}
}else{
	$m1 = 'selected';
}

$n = count($data);
$cur_cir = 0;
$chk = 0;
$date_array = [];
$date_indexs = [];
$circulation_array = [];

$cur_month = get_month();
$mon_days = [31,28,31,30,31,30,31,31,30,31,30,31];
$weekday_array = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$dateFrom = date('Y-m-01');
$dateTo   = date('Y-m-t');

if(isset($_REQUEST['datefrom']) && $_REQUEST['datefrom'] != '') 
	$dateFrom = $_REQUEST['datefrom'];
if(isset($_REQUEST['dateto']) && $_REQUEST['dateto'] != '')
	$dateTo = $_REQUEST['dateto'];
//difference between two dates
$diff = date_diff(date_create($dateFrom),date_create($dateTo));
//count days
$total_num = $diff->format("%a");

// Initial arrays
$incDate = $dateFrom;
for($i=0;$i<=$total_num;$i++){
	array_push($date_array, $incDate);
	$nextDate = date('Y-m-d', strtotime($incDate. ' + 1 days'));
	$incDate = $nextDate;
	//echo "<br>";
}
foreach ($data as $key => $datavalue) {
	$circulation = $datavalue->circulation;
	$date_submitted = $datavalue->date_submitted;
	$temp = date('Y-m-d', strtotime($date_submitted));
	$key = array_search($temp, $date_array); 
	$circulation_array_temp[$key] = $circulation;
}

$circulation_standard = 0;
for($i=0; $i<=$total_num ;$i++){
	
	$circulation = 0;
	
	if($i != 0) 
		$circulation = $circulation_array[$i-1]; //Fetch previous element of array
	
	if(isset($circulation_array_temp[$i])){
		$circulation_standard = $circulation_array_temp[$i];
		$circulation_array[$i] = $circulation + $circulation_array_temp[$i];						
	}
	else
	{		
		$per_down = ($circulation_standard / 100) * 3.57;
		$cur_cir = round($circulation - $per_down);
		if($cur_cir <= 0){
			$cur_cir = 0;
			$circulation_array[$i] = $cur_cir;	
		}else{
			$circulation_array[$i] = $cur_cir;	
		}
	}
}
	foreach ($date_array as $key => $value) {
		$date_array[$key] = get_day($value)."-".get_month($value);
	}
	$lab_json = json_encode($date_array);
	$cir_json = json_encode($circulation_array);

	/* Data fetch from Query for Age , Publication and so on... */

	/* Start Country Data */
	$cc_label = [];
	$cc_series = [];
	foreach($cc_data as $row){
		array_push($cc_label, $row->country_name);
		array_push($cc_series, $row->num);
	}
	$country_data = [['Country', 'Total Country Data']];
	for($i=0;$i<count($cc_label);$i++) {
		$country_temp = [$cc_label[$i],$cc_series[$i]];
		array_push($country_data, $country_temp);
	}
	/* End Country Data */

	/* Starte Age Data */

	$age_label = [];
	$age_series = [];
	foreach($age_data as $row){
		array_push($age_label, $row->age);
		array_push($age_series, $row->num);
	}
	$resulting_arr = [['Age', 'Total Age Data']];
	for($i=0;$i<count($age_label);$i++) {
		$temp = [$age_label[$i],$age_series[$i]];
		array_push($resulting_arr, $temp);
	}
	/* End Age Data */

	$lang_label = [];
	$lang_series = [];
	foreach($lang_data as $row){
		array_push($lang_label, $row->language);
		array_push($lang_series, $row->num);
	}
	$lang_array = [['Language', 'Total Language Data']];
	for($i=0;$i<count($lang_label);$i++) {
		$lang_temp = [$lang_label[$i],$lang_series[$i]];
		array_push($lang_array, $lang_temp);
	}

	/* Start Subject Data */
	$subj_label = [];
	$subj_series = [];
	foreach($subject_data as $row){
		array_push($subj_label, $row->pub_subject);
		array_push($subj_series, $row->num);
	}
	/* End Subject Data */

	$adsize_label = [];
	$adsize_series = [];
	foreach($adsize_data as $row){
		array_push($adsize_label, $row->template_title);
		array_push($adsize_series, $row->num);
	}

	$pub_label = [];
	$pub_series = [];
	foreach($pub_data as $row){
		array_push($pub_label, $row->company_name);
		array_push($pub_series, $row->pub_count);
	}
	$publication_data = [['Publication', 'Total Publication Data']];
	for($i=0;$i<count($pub_label);$i++) {
		$pub_temp = [$pub_label[$i],$pub_series[$i]];
		array_push($publication_data, $pub_temp);
	}

	$edition_label = [];
	$edition_series = [];
	foreach($edit_data as $row){
		array_push($edition_label, $row->edition);
		array_push($edition_series, $row->edition_count);
	}

	$cir_label = [];
	$cir_series = [];
	foreach($cir_data as $row){
		array_push($cir_label, $row->circulation);
		array_push($cir_series, $row->circulation_count);
	}

	$price_label = [];
	$price_series = [];
	foreach($ad_data as $row){
		array_push($price_label, $row->price);
		array_push($price_series, $row->price_count);
	}
	?>
	<div class="container">
	<div class="custom-row">
		<div class="row">
			<div class="col-sm-12">
				<div class="chart-block">
					<div class="chart-block-header">
						<form id="frm-view-method" method="GET" action="/advertiser/media/dashboard" style="display:inline-block;">{{csrf_field()}}
							<span style="color:#444;font-size:18px;"><?php echo date('Y / m'); ?> - Chart (Circulation/date),</span>&nbsp;
							<span style="color:#444;font-size:18px;"> Date Range :&nbsp;</span>
							@if(isset($_GET['datefrom']) && isset($_GET['dateto']))            
					          <input id="datefrom" type="text" class="start_date input-sm" name="datefrom" placeholder="Date from" value="{{$_GET['datefrom']}}"><span style="color: black;"> : </span> 
					          <input id="dateto" type="text" class="end_date input-sm" name="dateto" placeholder="Date to" value="{{$_GET['dateto']}}">
					        @else
					          <input id="datefrom" type="text" class="start_date input-sm" name="datefrom" placeholder="Date from" value="<?php echo date('Y-m-').'01'; ?>"><span style="color: black;"> : </span> 
					          <input id="dateto" type="text" class="end_date input-sm" name="dateto" placeholder="Date to" value="<?php echo date('Y-m-t'); ?>">
					        @endif
						</form>	
					</div>					
					<div><canvas id="circulation_data"></canvas></div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12 chart-header">
			<?php
				$cpm = 0;
				$budget = 0;
				$impression = 0;
				foreach ($data as $key => $value) {
					$cpm += $value->cost_single;
					$budget += $value->cost_subscription;
					$impression += $value->circulation;
				}
			if(count($data)>0){
				$cost_count = count($data);
			}
			else{
				$cost_count = 1;	
			}
			 ?>
				CPM= <?php echo round($cpm/$cost_count,2);  ?>$  |  Budget = <?php echo round($budget/$cost_count);  ?>  |  Impression= <?php echo round($impression/$cost_count);  ?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6">
				<div class="chart-block">
					<div class="chart-block-header">
						<div class="chart-title">Age</div>
					</div>
					<div id="age_data" class="google-chart-block"></div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="chart-block">
					<div class="chart-block-header">
						<div class="chart-title">Country</div>
					</div>							
					<div id="country_data" class="google-chart-block"></div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="chart-block">
					<div class="chart-block-header">
						<div class="chart-title">Subjects</div>
					</div>
					<div><canvas id="subject_d"></canvas></div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="chart-block">
					<div class="chart-block-header">
						<div class="chart-title">Language</div>
					</div>
					<div id="langu_data" class="google-chart-block"></div>
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="col-sm-6">
				<div class="chart-block">
					<div class="chart-block-header">
						<div class="chart-title">Publication</div>
					</div>
					<div id="pub_id" class="google-chart-block"></div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="chart-block">
				<div class="chart-block-header">
						<div class="chart-title">AD size</div>
					</div>
					<div><canvas id="ad_size"></canvas></div>
				</div>
			</div>
			
		</div>
		<div class="row">
			<div class="col-sm-6">
			<div class="chart-block">
					<div class="chart-block-header">
						<div class="chart-title">AD Price</div>
					</div>
					<div><canvas id="price_id"></canvas></div>
				</div>

			</div>
			<div class="col-sm-6">
				<div class="chart-block">
				<div class="chart-block-header">
						<div class="chart-title">Circulations</div>
					</div>
					<div><canvas id="cir_id"></canvas></div>
				</div>
			</div>
			<div class="col-sm-6 col-sm-offset-3">
				<div class="chart-block">
				<div class="chart-block-header">
						<div class="chart-title">Edition type</div>
					</div>
					<div><canvas id="edi_id"></canvas></div>
				</div>
			</div>
		</div>		
		</div>		
	</div>

	<!-- Chart For Country Data -->
	<script>
		google.charts.load('current', {'packages':['corechart']});
	    google.charts.setOnLoadCallback(drawChart);

	    function drawChart() {
	      var country_data = <?php echo json_encode($country_data);?>;
	      var data = google.visualization.arrayToDataTable(country_data);
	      var chart = new google.visualization.PieChart(document.getElementById('country_data'));
	      chart.draw(data);
	    }
	</script>

	<!-- Chart For Age Data -->
	<script>
		google.charts.load('current', {'packages':['corechart']});
	    google.charts.setOnLoadCallback(drawChart);

	    function drawChart() {
	      var resultdata = <?php echo json_encode($resulting_arr);?>;
	      var data = google.visualization.arrayToDataTable(resultdata);
	      var options = { pieHole: 0.3 }
	      var chart = new google.visualization.PieChart(document.getElementById('age_data'));
	      chart.draw(data , options);
	    }
	</script>

	<!-- Chart For Language Data -->
	<script>
		google.charts.load('current', {'packages':['corechart']});
	    google.charts.setOnLoadCallback(drawChart);

	    function drawChart() {
	      var langdata = <?php echo json_encode($lang_array);?>;
	      var data = google.visualization.arrayToDataTable(langdata);
	      var options = { pieHole: 0.3 }
	      var chart = new google.visualization.PieChart(document.getElementById('langu_data'));
	      chart.draw(data , options);
	    }
	</script>

	<!-- Chart For Subject Data -->
	<script>
		var sub_data = document.getElementById("subject_d").getContext('2d');
		var subject_d = new Chart(sub_data, {
			type: 'bar',
			data: {
				labels: <?php echo json_encode($subj_label);?>,
				datasets: [{
					label: 'All Subject Data',
					data: <?php echo json_encode($subj_series);?>,
					backgroundColor: [
					"#7460ee",
					"#009efb",
					"#55ce63",
					"#ffbc34",
					"#2f3d4a"
				
					],
					borderColor: [
					"#7460ee",
					"#009efb",
					"#55ce63",
					"#ffbc34",
					"#2f3d4a"
					],
					borderWidth: 1
				}]
			},
			options: {
				legend: {
              		display: false
            	},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				}
			}
		});
	</script>

	<!-- Chart For Ad Sizes Data -->
	<script>
		var add_data = document.getElementById("ad_size").getContext('2d');
		var ad_size = new Chart(add_data, {
			type: 'bar',
			data: {
				labels: <?php echo json_encode($adsize_label);?>,
				datasets: [{
					label: 'All AD Size Data',
					data: <?php echo json_encode($adsize_series);?>,
					backgroundColor: [
					"#009efb",
					"#2f3d4a",
					"#55ce63",
					"#7460ee",				
					"#edf1f5"
					],
					borderColor: [
					"#009efb",
					"#2f3d4a",
					"#55ce63",
					"#7460ee",				
					"#edf1f5"
					],
					borderWidth: 1
				}]
			},
			options: {
				legend: {
              		display: false
            	},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				}
			}
		});
	</script>

	<!-- Chart For Publication Data -->
	<script>
		google.charts.load('current', {'packages':['corechart']});
	    google.charts.setOnLoadCallback(drawChart);

	    function drawChart() {
	      var publication_all = <?php echo json_encode($publication_data);?>;
	      var data = google.visualization.arrayToDataTable(publication_all);
	      var chart = new google.visualization.PieChart(document.getElementById('pub_id'));
	      chart.draw(data);
	    }  
	</script>

	<!-- Chart For Edition Type Data -->
	<script>
		var edition_d = document.getElementById("edi_id");
		var edi_data = {
			labels: <?php echo json_encode($edition_label);?>,
			datasets: [
			{
				data: <?php echo json_encode($edition_series);?>,
				backgroundColor: [
				"#2f3d4a",
				"#ffbc34",
				"#55ce63",
				"#ffbc34",
				"#2f3d4a"
				],
				hoverBackgroundColor: [
			    "#2f3d4a",
				"#ffbc34",
				"#55ce63",
				"#ffbc34",
				"#2f3d4a"
				]
			}]
		};
		var myPieChart_edit = new Chart(edition_d,{
			type: 'pie',
			data: edi_data,
			options: {
				legend: {
          			display: false
        		},
        	}
		});
	</script>

	<!-- Chart For Ciculation Data -->
	<script>
		var circular_d = document.getElementById("cir_id").getContext('2d');
		var cir_id = new Chart(circular_d, {
			type: 'bar',
			data: {
				labels: <?php echo json_encode($cir_label);?>,
				datasets: [{
					label: 'All Circulation Data',
					data: <?php echo json_encode($cir_series);?>,
					backgroundColor: [
						"#7460ee",
						"#009efb",
						"#55ce63",
						"#2f3d4a",
						"#ffbc34"
					],
					borderColor: [
						"#7460ee",
						"#009efb",
						"#55ce63",
						"#2f3d4a",
						"#ffbc34"
					],
					borderWidth: 1
				}]
			},
			options: {
				legend: {
          			display: false
        		},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				}
			}
		});
	</script>

	<!-- Chart For Ad Prices Data -->
	<script>
		var price_data = document.getElementById("price_id").getContext('2d');
		var price_id = new Chart(price_data, {
			type: 'bar',
			data: {
				labels: <?php echo json_encode($price_label);?>,
				datasets: [{
					label: 'All AD Prices Data',
					data: <?php echo json_encode($price_series);?>,
					backgroundColor: [
					"#2f3d4a",
					"#7460ee",
					"#009efb",
					"#55ce63",
					"#edf1f5"
					],
					borderColor: [
					"#2f3d4a",
					"#7460ee",
					"#009efb",
					"#55ce63",
					"#edf1f5"
					],
					borderWidth: 1
				}]
			},
			options: {
				legend: {
          			display: false
        		},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				}
			}
		});
	</script>

	<!-- Chart For Circulation Main Data -->
	<script type="text/javascript">
		var lab_arr = <?php echo $lab_json; ?>;
		var cir_arr = <?php echo $cir_json; ?>;

		var circulation_d = document.getElementById("circulation_data").getContext('2d');
		var circulation_data = new Chart(circulation_d, {
			type: 'line',
			data: {
				labels: lab_arr,
				datasets: [{
					label: 'All Circulation Data',
					data: cir_arr,
					backgroundColor: [
					"rgba(116, 96, 238 , 0.3)"
					],
					borderColor: [
					"#7460ee",
					"#009efb",
					"#55ce63",
					"#ffbc34",
					"#2f3d4a"
					],
					pointBackgroundColor: "rgb(116, 96, 238)",
          			pointStyle: "rectRounded",
					borderWidth: 1
				}]
			},
			options: {
				legend: {
          			display: false
        		},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				}
			}
		});
	</script>
	<script>
		$(document).ready(function(){
			$('.start_date').datepicker({
			    format: 'yyyy-mm-dd',
			    startDate: '-1m',
			    endDate: '+1m'
			  });
			$('.end_date').datepicker({
			    format: 'yyyy-mm-dd',
			    startDate: '-1m',
			    endDate: '+1m'
			});
			$('.end_date , .start_date').on('changeDate', function(ev){
			    var start_d = $('.start_date').val();
			    var end_day = $('.end_date').val();
			    if($('.end_date').attr("id") == "dateto" && $(".start_day").val() != ""){
			        if(start_d > end_day){  
			          jQuery('.end_date').addClass('date-error');
			        }
			        else{
			          jQuery('.end_date').removeClass('date-error');
			          jQuery("#frm-view-method").submit();
			        }  
			    }
			    if($('.start_date').attr("id") == "dateFrom" && $(".end_date").val() != ""){
			        if(start_d > end_day){  
			          jQuery('.end_date').addClass('date-error');
			        }
			        else{
			          jQuery('.end_date').removeClass('date-error');
			          jQuery("#frm-view-method").submit();
			        }
			    }
			});
		});
	</script>
@endsection