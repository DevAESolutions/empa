<?php

    try {
        require 'DAO/baseDAO.php';
        require 'lib/functions.php';        
        $functions   = new functions();
        if (isset($_GET['data'])) {
            $dtParametro = $_GET['data']; /* = date('yy-m-d'); */
        }
        $idcliente = "";
        if (isset($_GET['idcliente'])) {
            $idcliente = $_GET['idcliente'];
        }

        if (($idcliente == "") or ( $dtParametro == "")) {
            exit;
        }
        $Assunto = "Processamento Id. Cliente: ".$idcliente."  Data: ".$dtParametro;
        $dadoConexao = new baseDAO();
        
        $functions->SendEmail("Inicio: " .$Assunto,"Inicio: " . date('d/m/Y H:i') . "<br> Id Cliente: " . $idcliente . "<br> DataProcessada: " . $dtParametro);
   
        $linha = $dadoConexao->update('situacoes_data', 'idsituacaodata_ref = null, idsituacaodata_ref = null,  quantidade_carga=0,quantidade_descarga=0,tempo_descarga=0,idsituacao=null,idcarga=null', [':data' => $dtParametro, ':idcliente' => $idcliente], ' cast(data as Date) in (:data) and idveiculo in (select idveiculo from cadveiculo where idcliente = :idcliente)');
        /* REGION CARGA */     
        
        echo "Inicio: ".date('d/m/Y H:i')."<br>"; 
        echo '<h1>Carga</h1><br><br><br><br>';
        $retorno       = $dadoConexao->select(
                    "select distinct ponto.idlogponto,  s.idsituacaodata,s.data,v.idequipamento," .
                    "       tp.type,s.latitude,s.longitude,v.fixo, s.idveiculo,  " .
                    "       ponto.nome as Area, ponto.tipo as Tipo, s.quantidade_carga,s.quantidade_descarga,   " .
                    "       vec.idcarga, vec.capacidade_carga,  ved.idcarga as iddescarga, IFNULL(ved.capacidade_descarga,vec.capacidade_descarga) as capacidade_descarga, " .
                    "       ced.descricao as Descarga, cec.descricao as Carga " .
                    "from situacoes_data s   " .
                    "left outer join cadveiculo v on v.idveiculo=s.idveiculo   " .
                    "left outer join cadvehicletype tp on tp.id_vtype=v.id_vtype   " .
                    "left outer join (select lgp.idlogponto, lgp.tipo, lgp.nome, lgp.regiao, lgp.idcliente, lgp.ativo   " .
                    "                 from log_ponto lgp) ponto   " .
                    "    on (Intersects(point(s.latitude,s.longitude), ponto.regiao) aND ponto.idcliente = v.idcliente AND ponto.ativo = 1)    " .
                    "left join cadveiculocarga vec on (vec.idveiculo = s.idveiculo and vec.idlogponto_carga = ponto.idlogponto)  " .
                    "left join cadclicarga cec on(cec.idcarga = vec.idcarga)" .
                    "left join cadveiculocarga ved on (ved.idveiculo = s.idveiculo and ved.idlogponto_descarga = ponto.idlogponto) " .
                    "left join cadclicarga ced on(ced.idcarga = ved.idcarga)" .
                    "where cast(s.data as date)  = '" . $dtParametro . "' " .
                    "  and fixo=0 and coalesce(s.velocidade,0) <= 2 and ponto.idlogponto is not null and v.idcliente ='" . $idcliente . "' " .               
                    "order by v.idequipamento, cast(s.data as DateTime)");        
        $IdLogPonto    = "";
        $IdEquipamento = "";
        foreach ($retorno as $v) {
            $qtdeCarga = 0;
            $qtdeDescarga =0;
            try {
                $sql = "";
                if (strtoupper($v['Tipo']) == strtoupper('Carga')){
                   $sql = " and (coalesce(s.quantidade_carga,0)<=0) "; 
                }
                else {
                   $sql = " and (coalesce(s.quantidade_descarga,0)<=0) "; 
                }
                
                $fixo = $dadoConexao->select("select * from (select s.idsituacaodata, s.data,v.idequipamento,s.latitude,s.longitude, v.fixo, " .
                        "                      s.quantidade_carga,s.quantidade_descarga, ponto.idlogponto, ponto.idcliente, " .
                        "                     ((select (111.111 * DEGREES(ACOS(LEAST(1.0, COS(RADIANS(" . $v["latitude"] . ")) " .
                        "                            * COS(RADIANS(s.latitude))" .
                        "                            * COS(RADIANS(" . $v["longitude"] . "-s.longitude))" .
                        "                            + SIN(RADIANS(" . $v["latitude"] . "))" .
                        "                            * SIN(RADIANS(s.latitude)))))))* 1000) as DistanciaCalculada " .
                        " from situacoes_data s " .
                        " left outer join cadveiculo v on v.idveiculo=s.idveiculo " .
                        " inner join (select lgp.idlogponto, lgp.tipo, lgp.nome, lgp.regiao, lgp.idcliente, lgp.ativo   " .
                        "             from log_ponto lgp) ponto   " .
                        "    on (Intersects(point(s.latitude,s.longitude), ponto.regiao) and ponto.idlogponto = '" . $v['idlogponto'] . "') " .
                        " where ucase(v.tipo_area) = ucase('".$v['Tipo']."') and fixo=1 and day(s.data) = Day('" . $v['data'] . 
                        "') and s.data  <= '" . $v['data'] .
                        "'  and v.idcliente ='" . $idcliente ."' ". $sql.                        
                        " ) as x order by DistanciaCalculada limit 1");
                $s = $fixo->fetchAll(\PDO::FETCH_OBJ);
                if ($fixo->rowCount() == 0)
                    $IdLogPonto = "";

                if ($fixo->rowCount() > 0) {       
                    $dadoConexao->update( 'situacoes_data',                                          
                                          ' idsituacaodata_ref = :idsituacaodata_ref, '.
                                          ' distancia_ref = :distancia_ref', [                                          
                                          ':id'               => $v['idsituacaodata'],
                                          ':idsituacaodata_ref' => $s[0]->idsituacaodata ,
                                          ':distancia_ref' => $s[0]->DistanciaCalculada],                                          
                                          ' idsituacaodata = :id');                
                    //Se estiver na area de Cobertura Faz o calculo 
                    if (!empty($v['Area'])) {
                        //Calculo de carga para fixo
                        if ($s[0]->fixo == 1) {
                            $calculoDistancia = $dadoConexao->select(
                                    " select ls.idsituacao, cds.situacao, ls.distancia, ls.ordem, " .
                                    "        ((select min(111.111 * DEGREES(ACOS(LEAST(1.0, COS(RADIANS(" . $v["latitude"] . ")) " .
                                    "                            * COS(RADIANS(" . $s[0]->latitude . "))" .
                                    "                            * COS(RADIANS(" . $v["longitude"] . "-" . $s[0]->longitude . "))" .
                                    "                            + SIN(RADIANS(" . $v["latitude"] . "))" .
                                    "                            * SIN(RADIANS(" . $s[0]->latitude . ")))))))* 1000) as DistanciaCalculada " .
                                    " from log_ponto_situacoes ls " .
                                    " left outer join cadsituacao cds on(cds.idsituacao = ls.idsituacao) " .
                                    " where ls.idlogponto = '" . $s[0]->idlogponto .
                                    "' and ((select min(111.111 * DEGREES(ACOS(LEAST(1.0, COS(RADIANS(" . $v["latitude"] . ")) " .
                                    "               * COS(RADIANS(" . $s[0]->latitude . "))" .
                                    "                                          * COS(RADIANS(" . $v["longitude"] . "-" . $s[0]->longitude . "))" .
                                    "                                          + SIN(RADIANS(" . $v["latitude"] . "))" .
                                    "                                          * SIN(RADIANS(" . $s[0]->latitude . "))))))) * 1000) <= ls.distancia  " .
                                    " order by  ls.distancia, ls.ordem " .
                                    "limit 1 ");
                            $distancia        = $calculoDistancia->fetchAll(\PDO::FETCH_OBJ);
                            if ($calculoDistancia->rowCount() > 0) {
                                //Verificando Situacao carregando
                                if (($IdLogPonto != $v['idlogponto']) || ($IdEquipamento != $v['idequipamento'])) {
                                    $IdLogPonto    = $v['idlogponto'];
                                    $IdEquipamento = $v['idequipamento'];
                                    $retorno       = $dadoConexao->select("select sts.idsituacao " .
                                            "from log_ponto_situacoes sts " .
                                            "where sts.idlogponto = '" . $IdLogPonto . "' and sts.idsituacao is not null " .
                                            "order  by sts.distancia limit 0,1");
                                    $sitRetorno    = $retorno->fetchAll(\PDO::FETCH_OBJ);
                                    $sitCarregando = $sitRetorno[0]->idsituacao;
                                }
                                //Se estiver Carregando verifica a carga
                                if (($sitCarregando == $distancia[0]->idsituacao)) {                                    
                                    $retorno    = $dadoConexao->select(
                                            "select s.idsituacao, " .
                                            "       ponto.tipo, " .
                                            "       s.idsituacaodata, ".
                                            "       coalesce(s.quantidade_carga,0) as quantidade_carga," .
                                            "       coalesce(s.quantidade_descarga,0) as quantidade_descarga " .
                                            "from situacoes_data s " .
                                            "left outer join cadveiculo v on v.idveiculo=s.idveiculo " .
                                            "left outer join (select lgp.idlogponto, lgp.tipo, lgp.nome, lgp.regiao, lgp.idcliente, lgp.ativo   " .
                                            "                 from log_ponto lgp) ponto   " .
                                            "    on (Intersects(point(s.latitude,s.longitude), ponto.regiao) aND ponto.idcliente = v.idcliente AND ponto.ativo = 1)    " .
                                            "where v.fixo =0 and day(s.data) = Day('" . $v['data'] . "') and s.data <= '" . $v['data'] .
                                            "' and v.idveiculo ='" . $v['idveiculo'] .
                                            "' and ponto.ativo = 1 and v.idcliente ='" . $idcliente . "' and s.idsituacao is not null ".
                                            " and s.idsituacaodata <> ".$v['idsituacaodata'].  
                                            " and (s.quantidade_carga <>0 or s.quantidade_descarga <>0)".
                                            " order by s.data desc limit 1");
                                    $carregando = $retorno->fetchAll(\PDO::FETCH_OBJ);
                                    if (empty($carregando)) {    
                                      if (strtoupper($v['Tipo']) == strtoupper('Carga')){
                                        $qtdeCarga = $v['capacidade_carga'];
                                        $qtdeDescarga = 0;                        
                                      }
                                    }
                                    else
                                    if (($carregando[0]->idsituacao != $sitCarregando) &&
                                        (strtoupper($carregando[0]->tipo) != strtoupper($v['Tipo']))) {
                                        ///Trata Carga 
                                        if ((strtoupper($v['Tipo']) == strtoupper('Carga')) &&
                                                ($carregando[0]->quantidade_carga <= 0 )) {
                                            $qtdeCarga = $v['capacidade_carga'];
                                            $qtdeDescarga = 0;
                                        }else{
                                            $qtdeCarga = 0;
                                            $qtdeDescarga = $v['capacidade_descarga'];
                                        }
                                    }else{                                        
                                       $qtdeCarga = 0;
                                       $qtdeDescarga = 0;
                                    }
                                }                               
                                
                                $dadoConexao->update(
                                      'situacoes_data', 
                                       'idsituacao =:idsituacao, idcarga = :idcarga, quantidade_carga = :quantidade_carga,  '.
                                       ' quantidade_descarga = :quantidade_descarga, '. 
                                       ' idsituacaodata_ref = :idsituacaodata_ref, '.
                                       ' distancia_ref = :distancia_ref',
                                      [
                                      ':idsituacao'         => $distancia[0]->idsituacao,
                                      ':idcarga'            => $v['idcarga'],
                                      ':quantidade_carga'   => $qtdeCarga,
                                      ':quantidade_descarga'   => $qtdeDescarga,
                                      ':idsituacaodata_ref' => $s[0]->idsituacaodata,                                          
                                      ':distancia_ref' => $distancia[0]->DistanciaCalculada, 
                                      ':id'               => $v['idsituacaodata']], 
                                      ' idsituacaodata = :id');                
                               
                                $dadoConexao->update(
                                     'situacoes_data', 
                                     'quantidade_carga = ifnull(quantidade_carga,0) + :quantidade_carga, '.
                                     'idsituacao =:idsituacao, idcarga = :idcarga, quantidade_descarga = :quantidade_descarga, '.
                                     ' idsituacaodata_ref = :idsituacaodata_ref, '.
                                     ' distancia_ref = :distancia_ref', [
                                     ':idsituacao' => $distancia[0]->idsituacao,
                                     ':quantidade_carga' => $qtdeCarga,
                                     ':quantidade_descarga'   => $qtdeDescarga,
                                     ':id'               => $s[0]->idsituacaodata,
                                     ':idsituacaodata_ref' => $v['idsituacaodata'],
                                     ':idcarga'    => $v['idcarga'],
                                     ':distancia_ref' => $distancia[0]->DistanciaCalculada],                                          
                                     ' idsituacaodata = :id');
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                
            }
        } 
        /* REGION DESCARGA */
        echo '<br><br><br><br><h1>DesCarga</h1><br><br><br><br>';
        $descarga        = $dadoConexao->select("select distinct ponto.idlogponto,  s.idsituacaodata,s.data,v.idequipamento," .
                "       tp.type,s.latitude,s.longitude,v.fixo, s.idveiculo," .
                "       ponto.nome as Area, ponto.tipo as Tipo, s.quantidade_carga," .
                "       s.quantidade_descarga, vec.idcarga, vec.capacidade_carga," .
                "       ved.idcarga as iddescarga, IFNULL(ved.capacidade_descarga,vec.capacidade_descarga) as capacidade_descarga," .
                "       ced.descricao as Descarga, cec.descricao as Carga, " .
                "       IF(producao_por_hora  >0, producao_por_hora / 60, producao_por_hora) as producao_por_hora " .
                "from situacoes_data s " .
                "left outer join cadveiculo v on v.idveiculo=s.idveiculo " .
                "left outer join cadvehicletype tp on tp.id_vtype=v.id_vtype " .
                "left outer join (select lgp.idlogponto, lgp.tipo, lgp.nome, lgp.regiao, lgp.idcliente, lgp.ativo " .
                "from log_ponto lgp) ponto       on (Intersects(point(s.latitude,s.longitude), ponto.regiao) aND ponto.idcliente = v.idcliente AND ponto.ativo = 1) " .
                "left join cadveiculocarga vec on (vec.idveiculo = s.idveiculo and vec.idlogponto_carga = ponto.idlogponto) " .
                "left join cadclicarga cec on(cec.idcarga = vec.idcarga) " .
                "left join cadveiculocarga ved on (ved.idveiculo = s.idveiculo and ved.idlogponto_descarga = ponto.idlogponto) " .
                "left join cadclicarga ced on(ced.idcarga = ved.idcarga) " .
                "where cast(s.data as date)  = '" . $dtParametro . "' " .
                " and ((UCASE(ponto.tipo)=UCASE('Carga') and fixo=0 and coalesce(s.quantidade_carga,0) > 0  ) or  (UCASE(ponto.tipo)<>UCASE('Carga') and UCASE(v.tipo_area)=UCASE('DesCarga') and fixo=1)) " .
                " and  ponto.idlogponto is not null and coalesce(s.velocidade,0) <= 2 AND ponto.ativo = 1 and v.idcliente ='" . $idcliente . "' " .
                " order by cast(s.data as DateTime) ");
        $descarregar     = 0;
        $situacaoNaoFixo = 0;
        $idlogPonto      = 0;
        foreach ($descarga as $d) {
            $qtdeDescarga = 0;
            if (strtoupper($d['Tipo']) == strtoupper('Carga')) {
                $descarregar += $d['quantidade_carga'];
                $dataCarga       = $d['data'];
                $situacaoNaoFixo = $d['idsituacaodata'];
                $qtdeDescarga = $d['capacidade_descarga'];
            }
            else {
                if ($descarregar > 0) {
                    $idlogPonto    = $d['idlogponto'];
                    $minutes       = $functions->DiffData($dataCarga, $d['data']);
                    $saldoProduzir = round($d['producao_por_hora'] * $minutes, 2);
                    if ($saldoProduzir > $descarregar) {
                        $saldoProduzir = $descarregar;
                    }
                    $descarregar -= $saldoProduzir;
                    if ($d['capacidade_descarga'] > 0) {
                     $dadoConexao->update(
                                'situacoes_data', 'quantidade_descarga =  :quantidade_descarga, idlogponto_descarga = :idlogponto_descarga', 
                               [':quantidade_descarga' => $qtdeDescarga,
                                ':id'                  => $d['idsituacaodata'],
                                ':idlogponto_descarga' => $idlogPonto], 
                                ' idsituacaodata = :id');      
                        
                        $dadoConexao->update(
                                'situacoes_data', 'quantidade_descarga =  :quantidade_descarga, idlogponto_descarga = :idlogponto_descarga', 
                               [':quantidade_descarga' => $qtdeDescarga,
                                ':id'                  => $situacaoNaoFixo,
                                ':idlogponto_descarga' => $idlogPonto], 
                                ' idsituacaodata = :id');
                    }
                }
            }
        }
        //Ajuste ratear Descarga
        $RateioDescarga = $dadoConexao->select(
                " select Descaraga.idlogponto,count(distinct v.idequipamento) as Equipamento," .
                "        count(distinct v.id_vtype) as Tipo, min(s1.data) as MinData, " .
                "        max(s1.data) as MaxData, min(s1.idsituacaodata) as idsituacaodata,  " .
                "        (TIMESTAMPDIFF(minute,min(s1.data),max(s1.data)))  as Tempo_Min, QtDescarga " .
                "from situacoes_data s1  " .
                "inner join(select lgp.idlogponto,lgp.nome, min(s.data) as Inicio,max(s.data) as Final, " .
                "      	     TIMESTAMPDIFF(minute,min(s.data),max(s.data)) as Tempo_Minutos, " .
                "		     sum(quantidade_descarga) as QtDescarga " .
                "           from situacoes_data s " .
                "           left outer join log_ponto lgp  on(lgp.idlogponto = s.idlogponto_descarga) " .
                "           where cast(s.data as date) = '" . $dtParametro . "' and quantidade_descarga > 0  " .
                "           group by lgp.idlogponto, lgp.nome) as Descaraga on  " .
                "           (Descaraga.idlogponto =  s1.idlogponto and s1.data >= Descaraga.Inicio and s1.data <= Descaraga.Final) " .
                "inner join cadveiculo v on v.idveiculo=s1.idveiculo " .
                "where v.fixo = 1 and coalesce(s1.velocidade,0) > 0 and v.idcliente ='" . $idcliente . "' " .
                "group by  Descaraga.idlogponto");
        foreach ($RateioDescarga as $r) {
            //Tipo = 1 coloco a descarga no veiculo
            if ($r['Tipo'] == 1) {
                $dadoConexao->update(
                       'situacoes_data', 
                       'quantidade_descarga =  :quantidade_descarga, tempo_descarga = :tempo_descarga', 
                      [':quantidade_descarga' => $r['QtDescarga'],
                       ':tempo_descarga'      => $r['Tempo_Min'],
                       ':id'                  => $r['idsituacaodata']], 
                       ' idsituacaodata = :id');
            }
            else
            if ($r['Tipo'] == $r['Equipamento']) {
                $calculoRastreio = $dadoConexao->select(
                        "select  v.idequipamento, min(idsituacaodata) as idsituacaodata " .
                        "from situacoes_data s1 " .
                        "inner join cadveiculo v on v.idveiculo=s1.idveiculo " .
                        "where s1.idlogponto ='" . $r['idlogponto'] . "' and v.fixo = 1 " .
                        "and coalesce(s1.velocidade,0) > 0 and cast(s1.data as datetime) >= '" . $r['MinData'] .
                        "' and cast(s1.data as datetime) <= '" . $r['MaxData'] .
                        "' and v.tipo_area  = 'DESCARGA'  and coalesce(tempo_descarga,0) <=0 " .
                        " and v.idcliente ='" . $idcliente . "' " .
                        " group by v.idequipamento");
                foreach ($calculoRastreio as $c) {
                    $dadoConexao->update(
                           'situacoes_data', 
                           'quantidade_descarga =  :quantidade_descarga, tempo_descarga = :tempo_descarga', 
                          [':quantidade_descarga' => $r['QtDescarga'],
                           ':tempo_descarga'      => $r['Tempo_Min'],
                           ':id'                  => $c['idsituacaodata']], 
                           ' idsituacaodata = :id');
                }
            }
            else {
                //Rateia por tipo
                $CalculoPorTipo = $dadoConexao->select(
                        "select   v.id_vtype,COUNT(Distinct v.id_vtype) as Tipo, count(distinct v.idequipamento)as Equipamento, ".
                        "         min(s1.data) as MinData, max(s1.data) as MaxData, " .
                        "         (TIMESTAMPDIFF(minute,min(s1.data),max(s1.data)))  as Tempo_Min, QtDescarga  " .
                        "from situacoes_data s1  " .
                        "  inner join(select lgp.idlogponto,lgp.nome, min(s.data) as Inicio,max(s.data) as Final, " .
                        "	             TIMESTAMPDIFF(minute,min(s.data),max(s.data)) as Tempo_Minutos, " .
                        "		     sum(quantidade_descarga) as QtDescarga " .
                        "           from situacoes_data s " .
                        "           left outer join log_ponto lgp  on(lgp.idlogponto = s.idlogponto_descarga) " .
                        "           where cast(s.data as date) = '" . $dtParametro . "' and quantidade_descarga > 0 " .
                        "           group by lgp.idlogponto, lgp.nome) as Descaraga on  " .
                        "             (Descaraga.idlogponto = s1.idlogponto and s1.data >= Descaraga.Inicio and s1.data <= Descaraga.Final) " .
                        "inner join cadveiculo v on v.idveiculo=s1.idveiculo  " .
                        "where v.fixo = 1 and coalesce(s1.velocidade,0) > 0 " .
                        " and  cast(s1.data as datetime) >= '" . $r['MinData'] .
                        "' and cast(s1.data as datetime) <= '" . $r['MaxData'] . "' " .
                        "  and Descaraga.idlogponto='" . $r['idlogponto'] .
                        "' and coalesce(tempo_descarga,0) <=0 " .
                        "  and v.idcliente ='" . $idcliente . "' " .
                        " group by  v.id_vtype ");
                foreach ($CalculoPorTipo as $c) {
                    //Tipo = 1 coloco a descarga no veiculo
                    if ($c['Equipamento'] == 1) {
                        $VerificaEquipamento = $dadoConexao->select(
                                "select min(idsituacaodata) as idsituacaodata " .
                                " from situacoes_data s1  " .
                                " inner join cadveiculo v on v.idveiculo=s1.idveiculo " .
                                " where v.fixo = 1 and coalesce(s1.velocidade,0) > 0 " .
                                "  and  cast(s1.data as datetime) >= '" . $c['MinData'] . "' " .
                                "  and cast(s1.data as datetime) <= '" . $c['MaxData'] . "' " .
                                "  and s1.idlogponto='" . $r['idlogponto'] . "' and v.id_vtype = " . $c['id_vtype'] .
                                "  and v.idcliente ='" . $idcliente . "' " .
                                "  and coalesce(tempo_descarga,0) <=0");
                        $equipamento = $VerificaEquipamento->fetchAll(\PDO::FETCH_OBJ);
                        $dadoConexao->update(
                              'situacoes_data', 
                              'quantidade_descarga =  :quantidade_descarga, tempo_descarga = :tempo_descarga', 
                             [':quantidade_descarga' => $r['QtDescarga'],
                              ':tempo_descarga'      => $c['Tempo_Min'],
                              ':id'                  => $equipamento[0]->idsituacaodata], 
                              'idsituacaodata = :id');
                    }
                    else {
                        $TotalCarga   = $dadoConexao->select(
                                "select sum(Tempo_Minutos) as Tempo_Minutos, sum(ProdAferida) as ProdAferida " .
                                "from(select v.idequipamento, min(idsituacaodata) as idsituacaodata, " .
                                "            TIMESTAMPDIFF(minute,min(s1.data),max(s1.data)) as Tempo_Minutos, " .
                                "           ((TIMESTAMPDIFF(minute,min(s1.data),max(s1.data))) * (producao_por_hora / 60))  as ProdAferida   " .
                                "     from situacoes_data s1 " .
                                "     inner join cadveiculo v on v.idveiculo=s1.idveiculo " .
                                "     where s1.idlogponto ='" . $r['idlogponto'] . "' and v.fixo = 1 " .
                                "     and coalesce(s1.velocidade,0) > 0 and cast(s1.data as datetime) >= '" . $r['MinData'] .
                                "' and cast(s1.data as datetime) <= '" . $r['MaxData'] .
                                "' and v.tipo_area  = 'DESCARGA' and v.id_vtype =" . $c['id_vtype'] .
                                "  and v.idcliente ='" . $idcliente . "' " .
                                " group by v.idequipamento) as x");
                        $dataSetCarga = $TotalCarga->fetchAll(\PDO::FETCH_OBJ);
                        $totalTempo   = round($dataSetCarga[0]->Tempo_Minutos, 3);
                        $ProdAferida  = round($dataSetCarga[0]->ProdAferida, 2);
                        echo $totalTempo . "<br><br><br><br><br>";
                        $SomaDescarga = $dadoConexao->select(
                                "select v.idequipamento,(producao_por_hora/ 60) as producao_por_hora, min(idsituacaodata) as idsituacaodata, " .
                                "       TIMESTAMPDIFF(minute,min(s1.data),max(s1.data)) as Tempo_Minutos, " .
                                "       ((TIMESTAMPDIFF(minute,min(s1.data),max(s1.data))) * (producao_por_hora / 60))  as ProdAferida " .
                                "from situacoes_data s1 " .
                                "inner join cadveiculo v on v.idveiculo=s1.idveiculo " .
                                "where s1.idlogponto ='" . $r['idlogponto'] . "' and v.fixo = 1 " .
                                "and coalesce(s1.velocidade,0) > 0 and cast(s1.data as datetime) >= '" . $r['MinData'] .
                                "' and cast(s1.data as datetime) <= '" . $r['MaxData'] .
                                "' and v.tipo_area  = 'DESCARGA' and v.id_vtype =" . $c['id_vtype'] .
                                "  and v.idcliente ='" . $idcliente . "' " .
                                "  group by v.idequipamento");
                        foreach ($SomaDescarga as $f) {
                            $ProducaoTeorica = round(($totalTempo / $ProdAferida) * $f['ProdAferida'], 2);

                            echo $ProducaoTeorica . '<br><br><br>';
                            $dadoConexao->update(
                                    'situacoes_data', 'quantidade_descarga =  :quantidade_descarga, tempo_descarga = :tempo_descarga', [':quantidade_descarga' => $ProducaoTeorica,
                                  ':tempo_descarga'      => $f['Tempo_Minutos'],
                                  ':id'                  => $f['idsituacaodata']], ' idsituacaodata = :id');
                        }
                    }
                }
            }
        }
        $functions->SendEmail($Assunto,"Fim: " . date('d/m/Y H:i') . "<br> Id Cliente: " . $idcliente . "<br> DataProcessada: " . $dtParametro);
        echo "Fim: ".date('d/m/Y H:i')."<br>"; 
    } catch (Exception $ex) {
        $functions->SendEmail($Assunto, "Erro ao Processas: ".$ex->getMessage(). " --"
                . "  "  . date('d/m/Y H:i') . "<br> Id Cliente: " . $idcliente . "<br> DataProcessada: " . $dtParametro);
    }    





    