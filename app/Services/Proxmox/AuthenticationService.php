<?php

namespace App\Services\Proxmox;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class AuthenticationService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getAuthToken($ip, $username, $password)
    {
        try {
            $URL = 'https://' . $ip . ':8006/api2/json';
            if (strpos($username, '@') === false) {
                $username .= '@pam';
            }
            $password = Crypt::decrypt($password);
            $response = $this->client->request('POST', $URL . '/access/ticket', [
                'form_params' => ['username' => $username, 'password' => $password],
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'verify' => false
            ]);
            $authData = json_decode($response->getBody(), true);
            return $authData['data'];
        } catch (GuzzleException $e) {
            Log::channel('proxmox_sync')->error("Error de autenticación con Proxmox ({$ip}/{$username}): " . $e->getMessage());
            return null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::channel('proxmox_sync')->error("Error de desencriptación de contraseña para IP: {$ip}");
            return null;
        }
    }

    public function makeRequest($method, $url, $authData)
    {
        try {
            $headers = ['Content-Type' => 'application/json'];

            // DETECCIÓN DE TIPO DE AUTENTICACIÓN
            if (isset($authData['type']) && $authData['type'] === 'token') {
                // 1. Autenticación por API Token (Header Authorization)
                $headers['Authorization'] = $authData['header'];
            } else {
                // 2. Autenticación Clásica por Ticket (Cookie + CSRF)
                // Aseguramos compatibilidad si authData no tiene la estructura nueva
                $ticket = $authData['ticket'] ?? $authData;
                $csrf = $authData['CSRFPreventionToken'] ?? null;

                $headers['Cookie'] = "PVEAuthCookie={$ticket}";
                if ($csrf) {
                    $headers['CSRFPreventionToken'] = $csrf;
                }
            }

            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'verify' => false,
                'timeout' => 5
            ]);

            if ($response->getStatusCode() >= 400) {
                 throw new \Exception("La API devolvió estado error: " . $response->getStatusCode());
            }

            $json = json_decode($response->getBody(), true);
            // Algunas respuestas de Proxmox pueden no tener 'data' si son operaciones vacías, pero generalmente sí.
            return isset($json['data']) ? $json['data'] : $json;

        } catch (ConnectException $e) {
            Log::channel('proxmox_sync')->error("Fallo de conexión con Proxmox: " . $e->getMessage(), ['url' => $url]);
            throw new \Exception("No se pudo conectar al host {$url}. Verifique IP y puerto 8006.");
        } catch (ClientException $e) {
             // Captura errores 401/403 específicos
             if ($e->getResponse()->getStatusCode() == 401) {
                 throw new \Exception("Autenticación fallida. Verifique Token ID y Secret.");
             }
             throw new \Exception("Error de API Proxmox: " . $e->getMessage());
        } catch (GuzzleException $e) {
            Log::channel('proxmox_sync')->error("Error Guzzle: " . $e->getMessage());
            throw new \Exception("Error HTTP en comunicación con Proxmox.");
        }
    }

    public function getClient()
    {
        return $this->client;
    }
}
