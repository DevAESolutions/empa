<?php

  require 'lib/Conexao.php';

  class baseDAO {

      private $conexao;

      public function __construct() {
          $this->conexao = Conexao::getConnection();
      }

      public function select($sql) {
          if (!empty($sql)) {
              return $this->conexao->query($sql);
          }        
      }

      public function insert($table, $cols, $values) {
          try {
              if (!empty($table) && !empty($cols) && !empty($values)) {
                  $parametros = $cols;
                  $colunas    = str_replace(":", "", $cols);
                  $stmt       = $this->conexao->prepare("INSERT INTO $table ($colunas) VALUES ($parametros)");
                  $stmt->execute($values);
                  return $this->conexao->lastInsertId();
              }
              else {
                  return false;
              }
          } catch (Exception $ex) {
              
          }
      }

      public function prepare($sql, $values) {
          $stmt = $this->conexao->prepare($sql);
          $stmt->execute($values);
          return $stmt;
      }

      public function update($table, $cols, $values, $where = null) {
          if (!empty($table) && !empty($cols) && !empty($values)) {
              if ($where) {
                  $where = " WHERE $where ";
              }

              $stmt = $this->conexao->prepare("UPDATE $table SET $cols $where");
              $stmt->execute($values);

              return $stmt->rowCount();
          }
          else {
              return false;
          }
      }

      public function verificaFK($table, $campoID, $where) {
          if ((!empty($table)) && (!empty($where))) {
              //Verifica Chave Estrangeira
              $resultado  = $this->select("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS where COLUMN_NAMe = '" . $campoID .
                      "' and TABLE_NAME <> '" . $table . "'");
              $result     = $resultado->fetchAll();
              $listaErros = new ResultadoValidacao();
              foreach ($result as $row) {
                  $dados  = $this->select("select " . $campoID . " from " . $row['TABLE_NAME'] . " " . $where);
                  $result = $dados->fetchAll();
                  if (count($result) != 0) {
                      $listaErros->addErro($row['TABLE_NAME'], "Existe(m) Registro(s) relacionado(s) na tabela " . $row['TABLE_NAME']);
                  }
              }
              return $listaErros;
          }
      }

      public function delete($table, $where = null) {
          if (!empty($table)) {
              if ($where) {
                  $where = " WHERE $where ";
              }
              $stmt = $this->prepare("DELETE FROM $table $where", "");
              $stmt->execute();
              return $stmt->rowCount();
          }
          else {
              return false;
          }
      }

      public function obterParametro($Value) {
          $retorno   = $this->select("Select Valor from parametros where idParametro = " . $Value);
          $resultado = $retorno->fetchAll(\PDO::FETCH_OBJ);
          return $resultado[0]->Valor;
      }

  }
  