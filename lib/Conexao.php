<?php

    class Conexao {

        private static $connection = null;

        private function __construct() {
            
        }

        public static function getConnection() {
            $config           = array();
            $config['dbname'] = "brasiltracknew";
          /*  $config['host']   = "54.87.154.239:3334";
            $config['dbuser'] = "root";
            $config['dbpass'] = "cgvcgv";*/
            $config['host']   = "54.87.154.239:3311";
            $config['dbuser'] = "elias.rodrigues";
            $config['dbpass'] = "am.4e532c301";
            $driver           = "mysql";

            $pdoConfig = $driver . ":" . "host=" . $config['host'] . ";";
            $pdoConfig .= "dbname=" . $config['dbname'] . ";";

            try {
                if (self::$connection === null) {
                    self::$connection = new PDO($pdoConfig, $config['dbuser'], $config['dbpass']);
                    self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
            } catch (PDOException $e) {
                throw new Exception("Erro de conexão com o banco de dados", 500);
            }

            return self::$connection;
        }

    }
    