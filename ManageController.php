<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
use App\Publication;

class ManageController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }
	
	public function index($msg = ''){
		
		$userid = Auth::user()->id;

		$rows = Publication::where('pub_owner', '=', $userid)->orderBy('updated_at', 'desc')->get();
		
		return view('publisher/inventory', compact('rows'));
	}
	
	public function process($option, $pub_id, $ad_id = null){
		
		$userid = Auth::user()->id;
		
		switch($option){
			
			case 'viewad':
			
				//$issued_rows = DB::table('issued')->where('pub_id', '=', $pub_id )->get();
				$issued_rows = DB::select('SELECT * FROM `issued` i LEFT JOIN ad_space asp ON i.id = asp.issued_id where asp.issued_id is not null and asp.pub_id = '.$pub_id. ' and asp.is_delete=0 GROUP by i.id');

				if(count($issued_rows) > 0){
					if(isset($_GET['sel_issued'])){
						$selected_issuedid = $_GET['sel_issued'];
						if($selected_issuedid == "0"){
							$qry = '';
						}else{
							$qry = ' AND ad_space.issued_id='.$selected_issuedid;
						}
					}else{
						$selected_issuedid = 0;
						$qry = '';
					}
				}else{
					$qry = '';
				}
				
				$rows = DB::table('ad_space')->select('ad_space.*', 'advertiser_creative.creative', 'advertiser_creative.message', 'advertiser_creative.creative_state', 'users.name')->whereRaw('ad_space.ad_owner='.$userid.' AND ad_space.pub_id='.$pub_id.$qry. ' AND is_delete = 0')->orderBy('page_number')->leftJoin('advertiser_creative', 'ad_space.ad_id', '=', 'advertiser_creative.ad_id')->leftJoin('users', 'users.id', '=', 'ad_space.ad_buyer')->get();
				//print_r(dd($rows));
				$pub_row = DB::table('publication')->where('pub_id', '=', $pub_id)->first();
					if(isset($_REQUEST['remove_issue'])){
						$remove_issue = DB::table('ad_space')->where('pub_id', '=' ,$pub_id)->update(array('is_delete' => 2));
						$msg = 'You have Remove all Issues';
						return redirect()->action('ManageController@process', ['option'=>'viewad', 'pub_id'=>$pub_id, 'ad_id'=>$ad_id, 'msg'=>$msg]);
					}
				if($rows){
					return view('publisher/viewadspace', compact('rows', 'pub_row','issued_rows', 'selected_issuedid'));
				}else{
					$msg = 'No Ad Space';
					return redirect()->action('ManageController@index', ['msg'=>$msg]);
				}
				break;
				
			case 'viewadset':
			
				$adstate = DB::table('ad_space')->whereRaw('ad_id='.$ad_id.' AND ad_owner='.$userid)->first();
				
				If($adstate->adstate == 1){
					$rlt = DB::table('ad_space')->where('ad_id', '=', $ad_id)->update(['adstate'=>'2']);
				}else if($adstate->adstate == 2){
					$rlt = DB::table('ad_space')->where('ad_id', '=', $ad_id)->update(['adstate'=>'1']);
				}

				return redirect()->action('ManageController@process', ['option'=>'viewad', 'pub_id'=>$pub_id, 'ad_id'=>$ad_id]);

			case 'editpub':
			
				return redirect()->action('PublicationController@index', ['id'=>$pub_id, 'title'=>'Edit Publication']);
				break;
			
			case 'edittemp':
				
				//$row = Publication::where('pub_id', '=', $pub_id)->first();
				//$adstep = 1;
				//return view('publisher/edit_template', compact('row','adstep'));
				
				//$rows = DB::select('SELECT a.*, b.adstate FROM (SELECT * FROM ad_template WHERE pub_id = '.$pub_id.') AS a ,(SELECT * FROM ad_space WHERE pub_id = '.$pub_id.') AS b  WHERE a.ad_format = b.placement');

				$rows = DB::select('SELECT a.*, b.adstate FROM (SELECT * FROM ad_template WHERE pub_id = '.$pub_id.') AS a LEFT JOIN (SELECT * FROM ad_space WHERE pub_id = '.$pub_id.') AS b ON a.ad_format = b.placement GROUP BY ad_format ORDER BY a.style_id');
				
				$pub = DB::table('publication')->where('pub_id', '=', $pub_id)->first();
				
				return view('publisher/edit_template', compact('rows', 'pub'));
				
				break;
			
			case 'delete':
			
				$chk = DB::table('ad_space')->whereRaw('pub_id = '.$pub_id.' AND adstate <> 1')->get();
				
				if(count($chk)){
					
					//$rlt = Publication::where('pub_id', '=', $pub_id)->delete();
					$rlt = DB::table('publication')->where('pub_id',$pub_id)->update(['is_delete' => 1]);
					if($rlt){
						
						$ad_rows = DB::table('ad_space')->where('pub_id', '=', $pub_id)->get();
						
						if($ad_rows){
							
							foreach($ad_rows as $row){
								$aid = $row->ad_id;
								$bid_row = DB::table('bids')->where('ad_id', '=', $aid)->get();
								
								if($bid_row){
									//DB::table('bids')->where('ad_id', '=', $aid)->delete();
									DB::table('bids')->where('ad_id',$aid)->update(['is_delete' => 1]);
								}
								
								$fav_row = DB::table('favorites')->where('ad_id', '=', $aid)->get();
								
								if($fav_row){
									//DB::table('favorites')->where('ad_id', '=', $aid)->delete();
									DB::table('favorites')->where('ad_id',$aid)->update(['is_delete' => 1]);
								}
							}
							
							//DB::table('ad_space')->where('pub_id', '=', $pub_id)->delete();
							DB::table('ad_space')->where('pub_id',$pub_id)->update(['is_delete' => 3]);

						}
					}
					$msg = 'PUB-DO';
					
				}else{
					$msg = 'PUB-DF';
				}

				return redirect()->action('ManageController@index', ['msg'=>$msg]);
				
				break;
		}
	}
	
	public function adver_inventory($pub_id){
		
		$userid = Auth::user()->id;
		$row = Publication::where('pub_id', '=', $pub_id)->first();
		
		$ad_rows = DB::table('ad_space')->whereRaw('pub_id='.$pub_id.' AND adstate=1')->leftJoin('bids', 'ad_space.ad_id', '=', 'bids.ad_id')->where('bids.bidder', '=', $userid)->get();
		
		return view('advertiser/inventory', compact('row', 'ad_rows'));
	}
	
	public function adver_media(){
		
		$userid = Auth::user()->id;
		
		//$rows = DB::table('ad_space as a')->select('a.pub_id', 'a.closing_time', 'a.price_sold', 'b.circulation', 'b.date_submitted')->whereRaw('a.adstate != 1 AND a.ad_buyer = '.$userid.' AND a.closing_time < CURDATE()')->orderBy('a.closing_time', 'desc')->leftJoin('publication as b', 'a.pub_id', '=', 'b.pub_id')->get();
		
		//---- Get Chart Data
		$this_year = getdate()['year'];
		$this_month = getdate()['mon'];
		$this_day = getdate()['mday'];
		
		$start = $this_year . '-' . (string)($this_month-1) . '-01';
		$end = $this_year . '-' . (string)($this_month+1) . '-31';

		$data = DB::table('ad_space as a')->select('a.pub_id', 'a.closing_time', 'a.price_sold', 'b.circulation', 'b.date_submitted')->whereRaw('a.adstate != 1 AND a.ad_buyer = '.$userid)->orderBy('b.date_submitted')->leftJoin('publication as b', 'a.pub_id', '=', 'b.pub_id')->whereRaw('b.date_submitted BETWEEN "'.$start.'" AND "'.$end.'"')->get();

		//---- Get Country Data
		
		//$cc_data = DB::table('publication')->select('countries.country_name', DB::raw('COUNT(publication.country) as num'))->leftJoin('countries', 'publication.country', '=', 'countries.country_id')->groupBy('country')->orderBy('num')->limit(5)->get();
		
		$cc_sql = 'SELECT b.country_name, COUNT(a.country) AS num FROM (SELECT ad_space.*, publication.country FROM ad_space, publication WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id GROUP BY publication.country) AS a LEFT JOIN countries AS b ON a.country = b.country_id ORDER BY num LIMIT 5';
		
		$cc_data = DB::select($cc_sql);
		
		//---- Get Age Data
		
		$age_sql = 'SELECT "45-55" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND (publication.agefrom  BETWEEN 45 AND 55 OR publication.ageto BETWEEN 45 AND 55)) AS a GROUP BY age
		UNION
		SELECT "35-45" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND (publication.agefrom  BETWEEN 35 AND 45 OR publication.ageto BETWEEN 35 AND 45)) AS a GROUP BY age
		UNION
		SELECT "25-35" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND (publication.agefrom  BETWEEN 25 AND 35 OR publication.ageto BETWEEN 25 AND 35)) AS a GROUP BY age
		UNION
		SELECT "20-25" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND (publication.agefrom  BETWEEN 20 AND 25 OR publication.ageto BETWEEN 20 AND 25)) AS a GROUP BY age
		UNION
		SELECT "18-20" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND (publication.agefrom  BETWEEN 18 AND 20 OR publication.ageto BETWEEN 18 AND 20)) AS a GROUP BY age';
		
		/*$age_data = DB::select('SELECT "45-55" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 45 AND 55 OR ageto BETWEEN 45 AND 55 GROUP BY age
		UNION
		SELECT "35-45" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 35 AND 45 OR ageto BETWEEN 35 AND 45 GROUP BY age
		UNION
		SELECT "25-45" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 35 AND 45 OR ageto BETWEEN 35 AND 45 GROUP BY age
		UNION
		SELECT "20-25" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 20 AND 25 OR ageto BETWEEN 20 AND 25 GROUP BY age
		UNION
		SELECT "18-20" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 18 AND 20 OR ageto BETWEEN 18 AND 20 GROUP BY age'); */
		
		$age_data = DB::select($age_sql);
		
		//----- Get Language Data
		
		$lang_sql = 'SELECT b.language, COUNT(b.language) AS num FROM ad_space AS a, publication AS b WHERE a.adstate != 1 AND a.ad_buyer = '.$userid.' AND a.pub_id = b.pub_id GROUP BY b.language LIMIT 5';
		
		$lang_data = DB::select($lang_sql);
		
		//$lang_data = DB::table('publication')->select('language', DB::raw('COUNT(language) as num'))->groupBy('language')->limit(5)->get();
		
		//----- Get Subject Data;
		
		$subj_sql = 'SELECT b.pub_subject, COUNT(b.pub_subject) AS num FROM ad_space AS a, publication AS b WHERE a.adstate != 1 AND a.ad_buyer = '.$userid.' AND a.pub_id = b.pub_id GROUP BY b.pub_subject LIMIT 5';
		
		$subject_data = DB::select($subj_sql);
		
		//$subject_data = DB::table('publication')->select('pub_subject', DB::raw('COUNT(pub_subject) as num'))->groupBy('pub_subject')->limit(5)->get();
		
		//----- Get Publication Data;
		
		$pub_data = DB::select('SELECT a.pub_owner, a.pub_count, users.company_name FROM (SELECT b.pub_owner, COUNT(b.pub_owner) pub_count FROM ad_space AS a, publication AS b WHERE a.adstate != 1 AND a.ad_buyer = '.$userid.' AND a.pub_id = b.pub_id GROUP BY pub_owner ORDER BY pub_count ASC LIMIT 5) AS a	LEFT JOIN users ON a.pub_owner = users.id');
		
		//----- Get Edition
		
		$edition_sql = 'SELECT b.edition, COUNT(b.edition) AS edition_count FROM ad_space AS a, publication AS b WHERE a.adstate != 1 AND a.ad_buyer = '.$userid.' AND a.pub_id = b.pub_id GROUP BY b.edition LIMIT 5';
		
		$edit_data = DB::select($edition_sql);
	
		//$edit_data = DB::select('SELECT edition, COUNT(edition) edition_count FROM publication GROUP BY edition ORDER BY edition_count ASC LIMIT 5');
		
		$cir_data = DB::select('SELECT 100000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.circulation  >= 100000) AS a
		UNION
		SELECT 50000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.circulation BETWEEN 50000 AND 99999) AS a
		UNION
		SELECT 25000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.circulation BETWEEN 25000 AND 49999) AS a
		UNION
		SELECT 10000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.circulation BETWEEN 10000 AND 24999) AS a');
		
		$ad_data = DB::select('SELECT "10000-20000" AS price, COUNT(start_price) AS price_count FROM ad_space WHERE start_price  BETWEEN 10000 AND 20000 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "5000-10000" AS price, COUNT(start_price) AS price_count FROM ad_space WHERE start_price  BETWEEN 5000 AND 10000 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "2500-5000" AS price, COUNT(start_price) AS price_count FROM ad_space WHERE start_price  BETWEEN 2500 AND 5000 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "1000-2500" AS price, COUNT(start_price) AS price_count FROM ad_space WHERE start_price  BETWEEN 1000 AND 2500 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "1-1000" AS price, COUNT(start_price) AS price_count FROM ad_space WHERE start_price  BETWEEN 1 AND 1000 AND adstate != 1 AND ad_buyer = '.$userid);
		
		$adsize_data = DB::select('SELECT b.template_title, COUNT(b.template_title) AS num FROM (SELECT * FROM ad_space WHERE ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' GROUP BY pub_id) AS a
		LEFT JOIN ad_template AS b ON a.pub_id = b.pub_id GROUP BY b.template_title LIMIT 5');
		
		return view('advertiser/media', compact('data', 'cc_data','age_data', 'lang_data', 'subject_data', 'pub_data', 'edit_data', 'cir_data', 'ad_data', 'adsize_data'));
	}
	public function new_media_layout(){
		$userid = Auth::user()->id;
		
		//---- Get Chart Data
		$first_day_this_month = date('Y-m-01');
		$last_day_this_month  = date('Y-m-t');
		if(isset($_GET['datefrom']) && isset($_GET['dateto'])){
			$first_day_this_month = $_GET['datefrom'];
			$last_day_this_month = $_GET['dateto'];
		}
		//$data = DB::table('ad_space as a')->select('a.pub_id', 'a.closing_time', 'a.price_sold', 'b.circulation', 'b.date_submitted')->whereRaw('a.adstate != 1 AND a.ad_buyer = '.$userid)->orderBy('b.date_submitted')->leftJoin('publication as b', 'a.pub_id', '=', 'b.pub_id')->whereRaw('b.date_submitted BETWEEN "'.$start.'" AND "'.$end.'"')->get();
		$media_dash = "SELECT a.pub_id, a.closing_time, a.price_sold, b.circulation, b.date_submitted, b.cost_subscription,b.cost_single from (SELECT * FROM ad_space as a where a.is_delete = 0 and a.adstate != 1 and a.ad_buyer = ".$userid.") a LEFT JOIN publication as b ON a.pub_id=b.pub_id where b.date_submitted BETWEEN '".$first_day_this_month."' and '".$last_day_this_month."'";
		$data = DB::select($media_dash);

		$cc_sql = "SELECT b.country_name,count(a.country) as num FROM (SELECT ad_space.*,publication.country FROM ad_space INNER JOIN publication ON ad_space.pub_id = publication.pub_id WHERE ad_space.is_delete=0 AND ad_space.adstate != 1 AND publication.date_submitted BETWEEN '".$first_day_this_month."' AND '".$last_day_this_month."' AND ad_space.ad_buyer = ".$userid. ") AS a LEFT JOIN countries AS b ON a.country = b.country_id GROUP by a.country ORDER BY b.country_name";
		
		$cc_data = DB::select($cc_sql);
		
		//---- Get Age Data
		
		$age_sql = 'SELECT "45-55" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND (publication.agefrom  BETWEEN 45 AND 55 OR publication.ageto BETWEEN 45 AND 55)) AS a GROUP BY age
		UNION
		SELECT "35-45" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND (publication.agefrom  BETWEEN 35 AND 45 OR publication.ageto BETWEEN 35 AND 45)) AS a GROUP BY age
		UNION
		SELECT "25-35" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND (publication.agefrom  BETWEEN 25 AND 35 OR publication.ageto BETWEEN 25 AND 35)) AS a GROUP BY age
		UNION
		SELECT "20-25" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND (publication.agefrom  BETWEEN 20 AND 25 OR publication.ageto BETWEEN 20 AND 25)) AS a GROUP BY age
		UNION
		SELECT "18-20" AS age, COUNT(a.pub_id) AS num FROM (SELECT publication.pub_id FROM ad_space, publication WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND (publication.agefrom  BETWEEN 18 AND 20 OR publication.ageto BETWEEN 18 AND 20)) AS a GROUP BY age';
		
		/*$age_data = DB::select('SELECT "45-55" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 45 AND 55 OR ageto BETWEEN 45 AND 55 GROUP BY age
		UNION
		SELECT "35-45" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 35 AND 45 OR ageto BETWEEN 35 AND 45 GROUP BY age
		UNION
		SELECT "25-45" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 35 AND 45 OR ageto BETWEEN 35 AND 45 GROUP BY age
		UNION
		SELECT "20-25" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 20 AND 25 OR ageto BETWEEN 20 AND 25 GROUP BY age
		UNION
		SELECT "18-20" AS age, COUNT(pub_id) AS num FROM publication WHERE agefrom  BETWEEN 18 AND 20 OR ageto BETWEEN 18 AND 20 GROUP BY age'); */
		
		$age_data = DB::select($age_sql);
		
		//----- Get Language Data
		
		$lang_sql = 'SELECT b.language, COUNT(b.language) AS num FROM ad_space AS a, publication AS b WHERE a.is_delete = 0 AND a.adstate != 1 AND b.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND a.ad_buyer = '.$userid.' AND a.pub_id = b.pub_id GROUP BY b.language';
		
		$lang_data = DB::select($lang_sql);
		
		//$lang_data = DB::table('publication')->select('language', DB::raw('COUNT(language) as num'))->groupBy('language')->limit(5)->get();
		
		//----- Get Subject Data;
		
		$subj_sql = 'SELECT b.pub_subject, COUNT(b.pub_subject) AS num FROM ad_space AS a, publication AS b WHERE a.is_delete = 0 AND a.adstate != 1 AND a.ad_buyer = '.$userid.' AND b.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND a.pub_id = b.pub_id GROUP BY b.pub_subject';
		
		$subject_data = DB::select($subj_sql);
		
		//$subject_data = DB::table('publication')->select('pub_subject', DB::raw('COUNT(pub_subject) as num'))->groupBy('pub_subject')->limit(5)->get();
		
		//----- Get Publication Data;
		
		$pub_data = DB::select('SELECT a.pub_owner, a.pub_count, users.company_name FROM (SELECT b.pub_owner, COUNT(b.pub_owner) pub_count FROM ad_space AS a, publication AS b WHERE a.is_delete = 0 AND a.adstate != 1 AND a.ad_buyer = '.$userid.' AND a.pub_id = b.pub_id AND b.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" GROUP BY pub_owner ORDER BY pub_count ASC) AS a	LEFT JOIN users ON a.pub_owner = users.id');
		
		//----- Get Edition
		
		$edition_sql = 'SELECT b.edition, COUNT(b.edition) AS edition_count FROM ad_space AS a, publication AS b WHERE a.is_delete = 0 AND a.adstate != 1 AND a.ad_buyer = '.$userid.' AND b.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND a.pub_id = b.pub_id GROUP BY b.edition';
		
		$edit_data = DB::select($edition_sql);
	
		//$edit_data = DB::select('SELECT edition, COUNT(edition) edition_count FROM publication GROUP BY edition ORDER BY edition_count ASC LIMIT 5');	

		$cir_data = DB::select('SELECT 100000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.circulation  >= 100000 AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'") AS a
		UNION
		SELECT 50000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND publication.circulation BETWEEN 50000 AND 99999) AS a
		UNION
		SELECT 25000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND publication.circulation BETWEEN 25000 AND 49999) AS a
		UNION
		SELECT 10000 AS circulation, COUNT(a.circulation) AS circulation_count FROM (SELECT publication.* FROM ad_space, publication 
		WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' AND ad_space.pub_id = publication.pub_id AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND publication.circulation BETWEEN 10000 AND 24999) AS a');

		$ad_data = DB::select('SELECT "10000-20000" AS price, COUNT(start_price) AS price_count FROM ad_space LEFT JOIN publication ON publication.pub_id = ad_space.pub_id WHERE ad_space.is_delete = 0 AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND start_price  BETWEEN 10000 AND 20000 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "5000-10000" AS price, COUNT(start_price) AS price_count FROM ad_space LEFT JOIN publication ON publication.pub_id = ad_space.pub_id WHERE ad_space.is_delete = 0 AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND start_price  BETWEEN 5000 AND 10000 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "2500-5000" AS price, COUNT(start_price) AS price_count FROM ad_space LEFT JOIN publication ON publication.pub_id = ad_space.pub_id WHERE ad_space.is_delete = 0 AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND start_price  BETWEEN 2500 AND 5000 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "1000-2500" AS price, COUNT(start_price) AS price_count FROM ad_space LEFT JOIN publication ON publication.pub_id = ad_space.pub_id WHERE ad_space.is_delete = 0 AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND start_price  BETWEEN 1000 AND 2500 AND adstate != 1 AND ad_buyer = '.$userid.'
		UNION
		SELECT "1-1000" AS price, COUNT(start_price) AS price_count FROM ad_space LEFT JOIN publication ON publication.pub_id = ad_space.pub_id WHERE ad_space.is_delete = 0 AND publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" AND start_price  BETWEEN 1 AND 1000 AND adstate != 1 AND ad_buyer = '.$userid);

		$adsize_data = DB::select('SELECT b.template_title, COUNT(b.template_title) AS num FROM (SELECT * FROM ad_space WHERE ad_space.is_delete = 0 AND ad_space.adstate != 1 AND ad_space.ad_buyer = '.$userid.' GROUP BY ad_space.pub_id) AS a
		LEFT JOIN ad_template AS b ON a.pub_id = b.pub_id LEFT JOIN publication ON a.pub_id = publication.pub_id WHERE publication.date_submitted BETWEEN "'.$first_day_this_month.'" AND "'.$last_day_this_month.'" GROUP BY b.template_title');

		return view('advertiser/media', compact('data', 'cc_data','age_data', 'lang_data', 'subject_data', 'pub_data', 'edit_data', 'cir_data', 'ad_data', 'adsize_data'));
	}
}
