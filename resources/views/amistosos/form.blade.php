@extends('template')

@section('content')
@if ($errors->any())
<div class="templatemo-content-widget yellow-bg">
    <i class="fa fa-times"></i>                
    <div class="media">
        <div class="media-body">
            <ul>
                @foreach($errors->all() as $error)
                <li><h2>{{ $error }}</h2></li>
                @endforeach
            </ul>
        </div>        
    </div>           
</div>     
@endif

<div class="templatemo-content-widget white-bg">
    <h2 class="margin-bottom-10">
    Novo {{substr_replace("Amistosos", "", -1)}}</h5>
    </h2>

    {!! Form::open(['route' => 'amistosos.store', 'method' => 'post', 'class' => 'form-horizontal']) !!}
    <div class="row form-group">
        <div class="col-md-12">
            {!! Html::decode(Form::label('tipo', 'Tipo <span class="obrigatorio">*</span>', ['class' => 'control-label'])) !!}
            {!! Form::select('tipo', ['2 contra 2', '1 contra 1'], NULL, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            {!! Html::decode(Form::label('time11_id', 'Time da Casa a Jogar <span class="obrigatorio">*</span>', ['class' => 'control-label'])) !!}
            {!! Form::select('time11_id', $times, NULL, ['class' => 'chzn-select form-control']) !!}
        </div>
    </div>
    <div class="row form-group time2">
        <div class="col-md-12">
            {!! Html::decode(Form::label('time12_id', 'Time da Casa a Não Jogar <span class="obrigatorio">*</span>', ['class' => 'control-label'])) !!}
            {!! Form::select('time12_id', $times, NULL, ['class' => 'chzn-select form-control']) !!}
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            {!! Html::decode(Form::label('time21_id', 'Time de Fora a Jogar <span class="obrigatorio">*</span>', ['class' => 'control-label'])) !!}
            {!! Form::select('time21_id', $times, NULL, ['class' => 'chzn-select form-control']) !!}
        </div>
    </div>
    <div class="row form-group time2">
        <div class="col-md-12">
            {!! Html::decode(Form::label('time22_id', 'Time de Fora a Não Jogar <span class="obrigatorio">*</span>', ['class' => 'control-label'])) !!}
            {!! Form::select('time22_id', $times, NULL, ['class' => 'chzn-select form-control']) !!}
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            {!! Html::decode(Form::label('valor', 'Valor <span class="obrigatorio">*</span>', ['class' => 'control-label'])) !!}
            {!! Form::select('valor', ['1000000' => '€ 1.000.000,00', '2000000' => '€ 2.000.000,00', '3000000' => '€ 3.000.000,00'], NULL, ['class' => 'form-control','onKeyDown' => 'Formata(this,20,event,2)', 'required' => 'true']) !!}
        </div>
    </div>
    <div class="form-group text-right">
        <button type="submit" class="templatemo-blue-button"><i class="fa fa-plus"></i> Salvar</button>
        <a class="templatemo-white-button" href="{{ route('amistosos.index') }}"><i class="fa fa-arrow-left"></i> Voltar</a>
    </div>
    {!! Form::close() !!}

</div>
<script type="text/javascript">
    // $(".time2").hide();
    $("#tipo").change(function(){
        if($(this).val() == "0")
            $(".time2").show();
        else
            $(".time2").hide();
    });
</script>
@endsection