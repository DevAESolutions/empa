<?php

class Email {

    private $from;
    private $fromName;
    private $fromPassword;
    private $adress;
    private $adressName;
    private $cc;
    private $ccName;
    private $body;
    private $anexo;
    private $anexoName;
    private $subject;
    private $smtpHost;

    public function getSmtpHost() {
        return $this->smtpHost;
    }

    public function setSmtpHost($smtpHost) {
        $this->smtpHost = $smtpHost;
    }

    public function getFromPassword() {
        return $this->fromPassword;
    }

    public function setFromPassword($fromPassword) {
        $this->fromPassword = $fromPassword;
    }

    public function getFrom() {
        return $this->from;
    }

    public function setFrom($from) {
        $this->from = $from;
    }

    public function getFromName() {
        return $this->fromName;
    }

    public function setFromName($fromName) {
        $this->fromName = $fromName;
    }

    public function getAdress() {
        return $this->adress;
    }

    public function setAdress($adress) {
        $this->adress = $adress;
    }

    public function getAdressName() {
        return $this->adressName;
    }

    public function setAdressName($adressName) {
        $this->adressName = $adressName;
    }

    public function getCc() {
        return $this->cc;
    }

    public function setCc($cc) {
        $this->cc = $cc;
    }

    public function getCcName() {
        return $this->ccName;
    }

    public function setCcName($ccName) {
        $this->ccName = $ccName;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function getBody() {
        return $this->body;
    }

    public function setAnexo($anexo) {
        $this->anexo = $anexo;
    }

    public function getAnexo() {
        return $this->anexo;
    }

    public function setAnexoName($anexoName) {
        $this->anexoName = $anexoName;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function getAnexoName() {
        return $this->anexoName;
    }

}
