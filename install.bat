@echo off
@echo { 																				> workspace.code-workspace
@echo 	"folders": [																	>> workspace.code-workspace
@echo 		{										        							>> workspace.code-workspace
@echo 			"path": "."																>> workspace.code-workspace
@echo 	    }								                							>> workspace.code-workspace
@echo 	],                                                  							>> workspace.code-workspace
@echo 	"settings": {	                                    							>> workspace.code-workspace
@echo 	    "intelephense.environment.includePaths": [	    							>> workspace.code-workspace
@echo 	      	"%USERPROFILE:\=\\%\\symfony\\vendor"       							>> workspace.code-workspace
@echo 	    ],                                       	    							>> workspace.code-workspace
@echo 	    "php.workspace.includePath": "%USERPROFILE:\=\\%\\symfony\\vendor",			>> workspace.code-workspace
@echo 	    "php.suggest.basic": false,                     							>> workspace.code-workspace
@echo 	}            	                                    							>> workspace.code-workspace
@echo }	            	                                    							>> workspace.code-workspace
@rmdir /s /q %USERPROFILE%\symfony
php composer.phar config cache-dir --unset
php composer.phar config cache-dir "%USERPROFILE:\=/%/symfony/cache"
php composer.phar config vendor-dir --unset
php composer.phar config vendor-dir "%USERPROFILE:\=/%/symfony/vendor"
php composer.phar config bin-dir --unset
php composer.phar config bin-dir "%USERPROFILE:\=/%/symfony/bin"
php composer.phar install
npm install