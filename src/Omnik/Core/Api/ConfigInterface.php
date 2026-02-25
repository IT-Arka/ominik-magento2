<?php

namespace Omnik\Core\Api;

interface ConfigInterface
{
    public const PARAM_TOKEN = 'token';
    public const PARAM_TOKEN_AUTHORIZATION = 'authorization';
    public const PARAM_APPLICATION_ID = 'application_id';
    public const PARAM_MODE = 'mode';
    public const PARAM_URL = 'url';
    public const PARAM_TIMEOUT = 'timeout';
    public const PARAM_APPLICATION_NAME = 'applicationName';
    public const PARAM_API_VERSION = 'apiVersion';
    public const PARAM_LIB_VERSION = 'libVersion';
    public const PARAM_USER_AGENT_SUFFIX = 'userAgentSufix';
    public const PARAM_LOGGER = 'logger';
    public const PARAM_LOGS_ENABLED = 'logsEnabled';
    public const PARAM_SELLER = 'seller';
    public const CUSTOMER_TYPE = "PJ";
    public const CUSTOMER_TYPE_PF = "PF";
    public const TELEPHONE_TYPE_NORMAL = "NORMAL";
    public const TELEPHONE_LOCAL_COMERCIAL = "COMERCIAL";
    public const TELEPHONE_LOCAL_CELULAR = "CELULAR";
    public const DDI = "+55";
    public const TYPE_DOCUMENT_CNPJ = "CNPJ";
    public const TYPE_DOCUMENT_CPF = "CPF";

    public const TELEPHONE = "telephone";
    public const CELLPHONE = "cellphone";
    public const STATUS_APPROVED = "APPROVED";

    public function get($key);

    public function set($key, $value);
}
