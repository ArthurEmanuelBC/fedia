<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get("auth/logout", function () {
	Auth::logout();
	return redirect('/');
});


// Authentication routes...
// Auth::routes();
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
// Route::get('auth/logout', 'Auth\AuthController@getLogout');

Route::group(['middleware' => 'auth'], function() {
	Route::get('/', function () {
		$temporada = App\Partida::all()->max('temporada');
		$contratacoes = App\Financeiro::selectRaw('valor, SUBSTRING(descricao, 25, CHAR_LENGTH(descricao)-25) as nome')->where('time_id',Auth::user()->time()->id)->where('descricao','LIKE','%Contratação de Jogador%')->orderBy('id','DESC')->limit(5)->get();
		$cartoes = App\Cartao::selectRaw('jogador_id,cor,campeonato,COUNT(*) as qtd')->where('time_id',Auth::user()->time()->id)->where('cumprido',0)->where('campeonato','!=','Amistoso')->where('temporada',$temporada)->groupBy('jogador_id','cor','campeonato')->get();
		$lesoes = App\Lesao::selectRaw('jogador_id,restantes')->where('time_id',Auth::user()->time()->id)->where('temporada',$temporada)->where('restantes','!=',0)->get();
		$gols = App\Gol::selectRaw('jogador_id,SUM(quantidade) as qtd')->where('time_id',Auth::user()->time()->id)->where('temporada',$temporada)->groupBy('jogador_id')->orderBy('qtd','desc')->limit(5)->get();
		// $lesoes_grafico = App\Lesao::selectRaw('jogador_id,SUM(rodadas) as qtd')->where('time_id',Auth::user()->time()->id)->where('temporada',$temporada)->groupBy('jogador_id')->orderBy('qtd','desc')->limit(5)->get();
		$aproveitamento = ['Vitória' => 0, 'Empate' => 0, 'Derrota' => 0];
		// Liga
		if(isset($temporada)){
			$partidas = App\Partida::where('temporada',$temporada)->where('campeonato','Liga')->whereRaw("resultado1 IS NOT NULL and resultado2 IS NOT NULL")->get();
			$times = App\Time::where('nome','!=','Mercado Externo')->get()->keyBy('id');
			$classificacao = [];
			foreach ($times as $key => $value) {
				$classificacao[$value->id] = ['P' => 0, 'J' => 0, 'V' => 0, 'E' => 0, 'D' => 0, 'GP' => 0, 'GC' => 0, 'SG' => 0, 'id' => $value->id, 'nome' => $times[$value->id]->nome, 'escudo' => $times[$value->id]->escudo];
			}
			foreach ($partidas as $key => $value) {
				$classificacao[$value->time1_id]["J"] += 1;
				$classificacao[$value->time2_id]["J"] += 1;
				$classificacao[$value->time1_id]["GP"] += $value->resultado1;
				$classificacao[$value->time2_id]["GP"] += $value->resultado2;
				$classificacao[$value->time1_id]["GC"] += $value->resultado2;
				$classificacao[$value->time2_id]["GC"] += $value->resultado1;
				$classificacao[$value->time1_id]["SG"] += $value->resultado1;
				$classificacao[$value->time1_id]["SG"] -= $value->resultado2;
				$classificacao[$value->time2_id]["SG"] += $value->resultado2;
				$classificacao[$value->time2_id]["SG"] -= $value->resultado1;
				if($value->resultado1 > $value->resultado2){
					$classificacao[$value->time1_id]["P"] += 3;
					$classificacao[$value->time1_id]["V"] += 1;
					$classificacao[$value->time2_id]["D"] += 1;
					if($value->time1_id == Auth::user()->time()->id)
						$aproveitamento['Vitória'] += 1;
					elseif($value->time2_id == Auth::user()->time()->id)
						$aproveitamento['Derrota'] += 1;
				} elseif ($value->resultado1 == $value->resultado2) {
					$classificacao[$value->time1_id]["P"] += 1;
					$classificacao[$value->time2_id]["P"] += 1;
					$classificacao[$value->time1_id]["E"] += 1;
					$classificacao[$value->time2_id]["E"] += 1;
					if($value->time1_id == Auth::user()->time()->id || $value->time2_id == Auth::user()->time()->id)
						$aproveitamento['Empate'] += 1;
				} else {
					$classificacao[$value->time2_id]["P"] += 3;
					$classificacao[$value->time2_id]["V"] += 1;
					$classificacao[$value->time1_id]["D"] += 1;
					if($value->time1_id == Auth::user()->time()->id)
						$aproveitamento['Derrota'] += 1;
					elseif($value->time2_id == Auth::user()->time()->id)
						$aproveitamento['Vitória'] += 1;
				}
			}

			$sort = array();
			foreach ($classificacao as $k => $c){
				$sort['P'][$k] = $c['P'];
				$sort['V'][$k] = $c['V'];
				$sort['SG'][$k] = $c['SG'];
				$sort['GP'][$k] = $c['GP'];
			}
			array_multisort($sort['P'], SORT_DESC, $sort['V'], SORT_DESC, $sort['SG'], SORT_DESC, $sort['GP'], SORT_DESC, $classificacao);

			$artilheiros = [];
			// Artilheiros Liga
			$artilheiros['Liga'] = DB::table('gols')->join('jogadors','gols.jogador_id','=','jogadors.id')->join('times','jogadors.time_id','=','times.id')->selectRaw('times.nome,times.escudo,jogadors.nome as jogador,SUM(quantidade) as qtd')->where('temporada',$temporada)->where('campeonato','Liga')->groupBy('jogadors.nome','times.nome','times.escudo')->orderBy('qtd','desc')->limit(8)->get();

			// Copa
			$copa = App\Partida::where('temporada',$temporada)->where('campeonato','Copa')->get()->keyBy(function($item){return $item['ordem']."|".$item['rodada'];});
			foreach ($copa as $key => $value) {
				if(is_null($value->resultado1) || is_null($value->resultado2))
					continue;
				if($value->time1_id == Auth::user()->time()->id){
					if($value->resultado1 > $value->resultado2)
						$aproveitamento['Vitória'] += 1;
					elseif ($value->resultado1 == $value->resultado2)
						$aproveitamento['Empate'] += 1;
					else
						$aproveitamento['Derrota'] += 1;
				} elseif($value->time2_id == Auth::user()->time()->id){
					if($value->resultado1 > $value->resultado2)
						$aproveitamento['Derrota'] += 1;
					elseif ($value->resultado1 == $value->resultado2)
						$aproveitamento['Empate'] += 1;
					else
						$aproveitamento['Derrota'] += 1;
				}
			}

			// Artilheiros Copa
			$artilheiros['Copa'] = DB::table('gols')->join('jogadors','gols.jogador_id','=','jogadors.id')->join('times','jogadors.time_id','=','times.id')->selectRaw('times.nome,times.escudo,jogadors.nome as jogador,SUM(quantidade) as qtd')->where('temporada',$temporada)->where('campeonato','Copa')->groupBy('jogadors.nome','times.nome','times.escudo')->orderBy('qtd','desc')->limit(8)->get();

			return view("index", ['temporada' => $temporada, 'classificacao' => $classificacao, 'copa' => $copa, 'times' => $times, 'artilheiros' => $artilheiros, 'contratacoes' => $contratacoes, 'lesoes' => $lesoes, 'cartoes' => $cartoes, 'gols' => $gols, 'aproveitamento' => $aproveitamento]);
		} else {
			return view("index", ['contratacoes' => $contratacoes, 'lesoes' => $lesoes, 'cartoes' => $cartoes, 'gols' => $gols, 'lesoes_grafico' => $lesoes_grafico]);
		}

	});

Route::resource("administracao/users","UserController");
Route::resource("times","TimeController");
Route::resource("transferencias","TransferenciumController");
Route::resource("partidas","PartidaController");
Route::resource("financeiros","FinanceiroController");
Route::resource("amistosos","AmistosoController");

Route::get('user_verificar_password', 'UserController@verificar_senha');
Route::get('user_verificar_login', 'UserController@verificar_login');

Route::get("partidas_temporadas", ['as' => 'partidas.temporadas', 'uses' => 'PartidaController@temporadas']);
Route::get("partidas_temporada_store", ['as' => 'partidas.temporada_store', 'uses' => 'PartidaController@temporada_store']);
Route::post("partidas_temporada_fotos", ['as' => 'partidas.temporada_fotos', 'uses' => 'PartidaController@temporada_fotos']);
Route::get("indisponiveis", ['as' => 'partidas.indisponiveis', 'uses' => 'PartidaController@indisponiveis']);
Route::get("partidas_time", ['as' => 'partidas.partidas', 'uses' => 'PartidaController@partidas']);

});
