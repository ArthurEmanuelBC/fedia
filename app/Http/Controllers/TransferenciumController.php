<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Transferencium;
use App\Time;
use App\Financeiro;
use App\Jogador;
use Illuminate\Http\Request;
use DB;

class TransferenciumController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request)
	{
		(strpos($request->fullUrl(),'order=')) ? $param = $request->order : $param = null;
		(strpos($request->fullUrl(),'?')) ? $signal = '&' : $signal = '?';
		(strpos($param,'desc')) ? $caret = 'up' : $caret = 'down';
		(isset($request->order)) ? $order = $request->order : $order = "transferencias.created_at DESC";
		if(isset($request->filtro)){
			if($request->filtro == "Limpar"){
				$request->valor = NULL;
				$transferencias = \DB::table(DB::raw('transferencias'))->join(DB::raw('times t1'),DB::raw('t1.id'),'=',DB::raw('transferencias.time1_id'))->join(DB::raw('times t2'),DB::raw('t2.id'),'=',DB::raw('transferencias.time2_id'))->join(DB::raw('jogadors'),DB::raw('jogadors.id'),'=',DB::raw('transferencias.jogador_id'))->select('transferencias.id','transferencias.created_at', 'jogadors.nome as jogador','valor','t1.nome as time1','t2.nome as time2')->orderByRaw($order)->paginate(30);
			}
			else{
				switch ($request->filtro) {
					case 'data':
					$clausure = "transferencias.created_at between '".date_format(date_create_from_format('d/m/Y', $request->valor), 'Y-m-d')." 00:00:00' and '".date_format(date_create_from_format('d/m/Y', $request->valor), 'Y-m-d')." 23:59:59'";
					break;
					case 'valor':
					$clausure = "valor = ".str_replace(",", ".", str_replace(".", "", str_replace("€", "", $request->valor)));
					break;
					case 'jogador':
					$clausure = "jogadors.nome LIKE '%$request->valor%'";
					break;
					case 'time1':
					$clausure = "time1.nome LIKE '%$request->valor%'";
					break;
					case 'time2':
					$clausure = "time2.nome LIKE '%$request->valor%'";
					break;
				}
				// $transferencias = Transferencium::whereRaw($clausure)->havingRaw($having)->orderByRaw($order)->paginate(30);
				$transferencias = \DB::table('transferencias')->join(DB::raw('times time1'),DB::raw('time1.id'),'=',DB::raw('transferencias.time1_id'))->join(DB::raw('times time2'),DB::raw('time2.id'),'=',DB::raw('transferencias.time2_id'))->join(DB::raw('jogadors'),DB::raw('jogadors.id'),'=',DB::raw('transferencias.jogador_id'))->select('transferencias.id','transferencias.created_at', 'jogadors.nome as jogador','valor','time1.nome as time1','time2.nome as time2')->whereRaw($clausure)->orderByRaw($order)->paginate(30);
			}
		}
		else
			$transferencias = \DB::table('transferencias')->join(DB::raw('times time1'),DB::raw('time1.id'),'=',DB::raw('transferencias.time1_id'))->join(DB::raw('times time2'),DB::raw('time2.id'),'=',DB::raw('transferencias.time2_id'))->join(DB::raw('jogadors'),DB::raw('jogadors.id'),'=',DB::raw('transferencias.jogador_id'))->select('transferencias.id','transferencias.created_at', 'jogadors.nome as jogador','valor','time1.nome as time1','time2.nome as time2')->orderByRaw($order)->paginate(30);
		return view('administracao.transferencias.index', ["transferencias" => $transferencias, "filtro" => $request->filtro, "valor" => $request->valor, "signal" => $signal, "param" => $param, "caret" => $caret]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$transferencium = new Transferencium();
		$times = Time::orderBy('nome')->lists('nome','id')->all();
		$jogadores = [];
		foreach (Jogador::all() as $key => $value) {
			if(empty($jogadores[$value->time_id]))
				$jogadores[$value->time_id] = [];
			$jogadores[$value->time_id][] = $value;
		}
		return view('administracao.transferencias.form', ["transferencium" => $transferencium, "url" => "administracao.transferencias.store", "method" => "post", "times" => $times, "jogadores" => $jogadores]);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
		$transferencium = new Transferencium();
		$transferencium->valor = str_replace(",", ".", str_replace(".", "", $request->input("valor")));
		$transferencium->time1_id = $request->input("time1_id");
		$transferencium->time2_id = $request->input("time2_id");
		$time1 = Time::findOrFail($request->input("time1_id"));
		if($time1->nome == "Mercado Externo"){
			$jogador = new Jogador();
			$jogador->nome = $request->input("jogador");
			$jogador->time_id = $request->input("time2_id");
			$jogador->save();
		} else {
			$jogador = Jogador::findOrFail($request->input("jogador_id"));
			$jogador->time_id = $request->input("time2_id");
			$jogador->save();
		}
		$transferencium->jogador_id = $jogador->id;
		$transferencium->save();
		if(!is_null($time1)){
			$time1->dinheiro += floatval(str_replace(",", ".", str_replace(".", "", $request->input("valor"))));
			$time1->save();
			Financeiro::create(['valor' => floatval(str_replace(",", ".", str_replace(".", "", $request->input("valor")))), 'operacao' => 0, 'descricao' => 'Venda de Jogador ('.$jogador->nome.')', 'time_id' => $time1->id]);
		}
		$time2 = Time::findOrFail($request->input("time2_id"));
		if(!is_null($time2)){
			$time2->dinheiro -= floatval(str_replace(",", ".", str_replace(".", "", $request->input("valor"))));
			$time2->save();
			Financeiro::create(['valor' => floatval(str_replace(",", ".", str_replace(".", "", $request->input("valor")))), 'operacao' => 1, 'descricao' => 'Contratação de Jogador ('.$jogador->nome.')', 'time_id' => $time2->id]);
		}
		return redirect()->route('administracao.transferencias.index')->with('message', 'Transferência cadastrada com sucesso!');
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$transferencium = Transferencium::findOrFail($id);
		$times = Time::lists('nome','id')->all();
		return view('administracao.transferencias.form', ["transferencium" => $transferencium, "url" => "administracao.transferencias.update", "method" => "put", "times" => $times]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @param Request $request
	 * @return Response
	 */
	public function update(Request $request, $id)
	{
		$transferencium = Transferencium::findOrFail($id);
		$transferencium->jogador = $request->input("jogador");
		$transferencium->valor = str_replace(",", ".", str_replace(".", "", $request->input("valor")));
		$transferencium->time1 = $request->input("time1");
		$transferencium->time2 = $request->input("time2");
		$transferencium->save();
		return redirect()->route('administracao.transferencias.index')->with('message', 'Transferência atualizada com sucesso!');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$transferencium = Transferencium::findOrFail($id);
		$jogador = Jogador::findOrFail($transferencium->jogador_id);
		$jogador->time_id = $transferencium->time1_id;
		$jogador->save();
		$time1 = Time::findOrFail($transferencium->time1_id);
		if(!is_null($time1)){
			$time1->dinheiro -= $transferencium->valor;
			$time1->save();
			Financeiro::where('descricao',"Venda de Jogador ($jogador->nome)")->where('time_id',$time1->id)->delete();
		}
		$time2 = Time::findOrFail($transferencium->time2_id);
		if(!is_null($time2)){
			$time2->dinheiro += $transferencium->valor;
			$time2->save();
			Financeiro::where('descricao',"Contratação de Jogador ($jogador->nome)")->where('time_id',$time2->id)->delete();
		}
		$transferencium->delete();
		return redirect()->route('administracao.transferencias.index')->with('message', 'Transferência deletado com sucesso!');
	}

}
