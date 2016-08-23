{
	"params":{
		"safemode":false,
		"name":"Generic Core",
		"version":"0.1.20130520",
		"api_version":"0.1.20130520",
		"api_auth":true,
		"api_client":"gcore",
		"api_name":"gcore",
		"trace":false,
		"support":"gcore@johnynsk.ru",
		"limit_max":300,
		"limit_default":30,
		"httproot":"/",
		"linkroot":"/",
		"authorize_package":"_std_auth",
		"trace_allow":[
		]
	},
	"packages":{
		"gcore_client":{
			"location":"core/gcore_client/gcore_client.php",
			"init":"core/gcore_client/init.php",
			"recommends":[
				"_auth"
			]
		},
		"_std_auth":{
			"location":"core/_std/_std_auth.php",
			"init":"core/_std/_std_auth_init.php",
			"params":{
				"file_clients":"core/std_auth_clients.json"
			}
		},
		"developer":{
			"location":"core/developer/developer.php",
			"init":"core/developer/init.php",
			"public":"core/developer/developer.js",
			"dependence":[
				"_std_auth"
			]
		},
		"web":{
			"disabled":true,
			"location":"core/web/web.php",
			"init":"core/web/init.php",
			"params":{
				"theme":"themes/class/theme.php"
			}
		}
	},
	"dataparams":{
		"limit":{
			"description":"Количество получаемых элементов",
			"type":"uint",
			"default":30,
			"limit":300
		},
		"offset":{
			"description":"Смещение результатов",
			"type":"uint",
			"default":0
		},
		"page":{
			"description":"Страница результатов метода",
			"type":"uint",
			"default":1
		},
		"order":{
			"type":"enum",
			"values":["asc","desc"],
			"description":"Направление сортировки"
		},
		"count":{
			"type":"bool",
			"description":"Возвращает только количество строк"
		},
		"api_key":{
			"description":"Ключ пользователя API",
			"required":true
		},
		"api_sig":{
			"description":"Подпись запроса",
			"required":true
		}
	}
}
