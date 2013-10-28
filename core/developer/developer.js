{
	"time":{
		"description":"Возвращает время из Timestamp",
		"params":{
			"timestamp":{
				"required":true,
				"type":"uint",
				"description":"timestamp"
			}
		}
	},
	"test":{
		"description":"Метод для тестирования авторизации",
		"auth":true,
		"params":{
			"string":{
				"description":"Возвращает эту строку в случае успешной авторизации"
			}
		}
	},
	"md5":{
		"description":"Возвращает md5",
		"params":{
			"string":{
				"required":true,
				"type":"string",
				"description":"Строка"
			}
		}
	},
	"ip2long":{
		"description":"Возвращает ip2long",
		"params":{
			"ip":{
				"required":true,
				"type":"string",
				"description":"IP-адрес"
			}
		}
	},
	"genSignature":{
		"auth":false,
		"description":"Generates signature for request.",
		"auth_ip":false,
		"allow_ip":[
		],
		"params":{
			"api_key":{
				"required":true,
				"type":"string",
				"description":"Your application api_key"
			},
			"api_secret":{
				"required":true,
				"type":"string",
				"description":"Your application api_secret"
			},
			"method_name":{
				"required":true,
				"type":"string",
				"description":"Method name"
			}
		}
	}
}
