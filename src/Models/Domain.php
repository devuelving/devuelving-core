<?php

namespace App;

use Carbon\Carbon;
use DonDominioAPI;

class Domain
{
    public $domain;
    public $dondominio;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->domain = \App\Franchise::get('domain');
        $this->dondominio = new DonDominioAPI([
            'apiuser' => env('DONDOMINIO_USER'),
            'apipasswd' => env('DONDOMINIO_PASSWORD'),
        ]);
    }

    /**
     * Función para obtener los datos del servicio
     *
     * @param string $infoType
     * @return void
     */
    public function service_getInfo($infoType)
    {
        $response = $this->dondominio->service_getInfo($this->domain, ['infoType' => $infoType]);
        if ($infoType == 'status') {
            $return = [
                'name' => $response->get('name'),
                'status' => $response->get('status'),
                'type' => $response->get('type'),
                'renewable' => $response->get('renewable'),
                'tsExpir' => Carbon::createFromFormat('Y-m-d', $response->get('tsExpir')),
                'tsExpirDays' => Carbon::createFromFormat('Y-m-d', $response->get('tsExpir'))->diffInDays(),
            ];
        } else if ($infoType == 'resources') {
            $return = [
                'subdomain_value' => $response->get('resources')['subdomain']['value'],
                'subdomain_max' => $response->get('resources')['subdomain']['max'],
                'subdomain_unit' => $response->get('resources')['subdomain']['unit'],
                'email_value' => $response->get('resources')['email']['value'],
                'email_max' => $response->get('resources')['email']['max'],
                'email_unit' => $response->get('resources')['email']['unit'],
            ];
        } else if ($infoType == 'serverinfo') {
            $return = [
                'smtpServer' => $response->get('serverinfo')['smtpServer'],
                'pop3Server' => $response->get('serverinfo')['pop3Server'],
                'imapServer' => $response->get('serverinfo')['imapServer'],
                'webmail' => $response->get('serverinfo')['webmail'],
            ];
        }
        return $return;
    }

    /**
     * Función para obtener los datos del dominio
     *
     * @return void
     */
    public function domain_getInfo()
    {
        $response = $this->dondominio->domain_getInfo($this->domain, ['infoType' => 'status']);
        $return = [
            'status' => $response->get('status'),
            'renewable' => $response->get('renewable'),
            'tsExpir' => Carbon::createFromFormat('Y-m-d', $response->get('tsExpir')),
            'tsExpirDays' => Carbon::createFromFormat('Y-m-d', $response->get('tsExpir'))->diffInDays(),
        ];
        return $return;
    }

    /**
     * Función para obtener el listado de los dominios
     *
     * @return void
     */
    public function service_mailList()
    {
        $return = [];
        $response = $this->dondominio->service_mailList($this->domain);
        for ($i=0; $i < count($response->get('mail')); $i++) {
            $return[] = [
                'entityID' => $response->get('mail')[$i]['entityID'],
                'name' => $response->get('mail')[$i]['name'],
                'password' => $response->get('mail')[$i]['password'],
            ];
        }
        return $return;
    }

    /**
     * Función para obtener el listado de DNS
     *
     * @return void
     */
    public function service_dnsList()
    {
        $return = [];
        $response = $this->dondominio->service_dnsList($this->domain);
        for ($i=0; $i < count($response->get('dns')); $i++) {
            $return[] = [
                'entityID' => $response->get('dns')[$i]['entityID'],
                'name' => $response->get('dns')[$i]['name'],
                'type' => $response->get('dns')[$i]['type'],
                'ttl' => $response->get('dns')[$i]['ttl'],
                'priority' => $response->get('dns')[$i]['priority'],
                'value' => $response->get('dns')[$i]['value'],
            ];
        }
        return $return;
    }

    /**
     * Función para obtener los datos de un registro dns
     *
     * @param mixed $service
     * @return void
     */
    public function service_dnsGetInfo($service)
    {
        $response = $this->dondominio->service_dnsGetInfo($this->domain, $service);
        $return = [
            'entityID' => $response->get('dns')[0]['entityID'],
            'name' => $response->get('dns')[0]['name'],
            'type' => $response->get('dns')[0]['type'],
            'ttl' => $response->get('dns')[0]['ttl'],
            'priority' => $response->get('dns')[0]['priority'],
            'value' => $response->get('dns')[0]['value'],
        ];
        return $return;
    }

    /**
     * Función para renovar el servicio
     *
     * @param string $service
     * @return void
     */
    public function service_renew($service)
    {
        try {
            return $this->dondominio->service_renew($service);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Función para renovar el dominio
     *
     * @param string $service
     * @param string $curExpDate
     * @return void
     */
    public function domain_renew($service, $curExpDate)
    {
        try {
            return $this->dondominio->domain_renew($service, [
                'curExpDate' => $curExpDate,
                'period' => 1
            ]);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Función para crear un registro dns
     *
     * @param mixed $name
     * @param mixed $type
     * @param mixed $value
     * @param mixed $ttl
     * @param mixed $priority
     * @return void
     */
    public function dns_create($name, $type, $value, $ttl = null, $priority = null)
    {
        try {
            return $this->dondominio->service_dnsCreate($this->domain, [
                'name' => $name,
                'type' => $type,
                'value' => $value,
                'ttl' => $ttl,
                'priority' => $priority,
            ]);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Función para actualizar un registro dns
     *
     * @param mixed $dns
     * @param mixed $value
     * @param mixed $ttl
     * @param mixed $priority
     * @return void
     */
    public function dns_update($dns, $value, $ttl = null, $priority = null)
    {
        try {
            return $this->dondominio->service_dnsUpdate($this->domain, $dns, [
                'value' => $value,
                'ttl' => $ttl,
                'priority' => $priority,
            ]);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Función para eliminar un registro dns
     *
     * @param string $code
     * @return void
     */
    public function delete_dns($code)
    {
        try {
            return $this->dondominio->service_dnsDelete($this->domain, $code);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }


    /**
     * Función para crear una nueva cuenta de correo electronico
     *
     * @param string $account
     * @param string $password
     * @return void
     */
    public function email_create($account, $password)
    {
        try {
            return $this->dondominio->service_mailCreate($this->domain, [
                'name' => $account . '@' . $this->domain,
                'password' => $password
            ]);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
    
    /**
     * Función para modificar una cuenta de correo electronico
     *
     * @param string $account
     * @param string $password
     * @return void
     */
    public function email_update($account, $password)
    {
        try {
            return $this->dondominio->service_mailUpdate($this->domain, $account, [
                'password' => $password
            ]);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Función para eliminar una cuenta de correo electronico
     *
     * @param string $account
     * @return void
     */
    public function delete_email($account)
    {
        try {
            return $this->dondominio->service_mailDelete($this->domain, $account);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
