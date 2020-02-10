document.addEventListener('DOMContentLoaded', function() {

    var documentationDeploymentRepositoryUrl = document.getElementById('documentation_deployment_repositoryUrl');
    if (documentationDeploymentRepositoryUrl) {
        documentationDeploymentRepositoryUrl.addEventListener('change', function() {
            const value = documentationDeploymentRepositoryUrl.value;
            const documentationRepositoryType = document.getElementById('documentation_deployment_repositoryType');
            if (value.indexOf('https://github.com') === 0) {
                documentationRepositoryType.setAttribute('readonly', true);
                for(i = 0; i < documentationRepositoryType.options.length; i++) {
                    if (documentationRepositoryType.options[i].value === 'github') {
                        documentationRepositoryType.selectedIndex = i;
                        documentationRepositoryType.options[i].disabled = false;
                    } else {
                        documentationRepositoryType.options[i].disabled = true;
                    }
                }
            } else if (value.indexOf('https://gitlab.com') === 0) {
                documentationRepositoryType.setAttribute('readonly', true);
                for(i = 0; i < documentationRepositoryType.options.length; i++) {
                    if (documentationRepositoryType.options[i].value === 'gitlab') {
                        documentationRepositoryType.selectedIndex = i;
                        documentationRepositoryType.options[i].disabled = false;
                    } else {
                        documentationRepositoryType.options[i].disabled = true;
                    }
                }
            } else if (value.indexOf('https://bitbucket.org') === 0) {
                documentationRepositoryType.setAttribute('readonly', true);
                for(i = 0; i < documentationRepositoryType.options.length; i++) {
                    if (documentationRepositoryType.options[i].value === 'bitbucket-cloud') {
                        documentationRepositoryType.selectedIndex = i;
                        documentationRepositoryType.options[i].disabled = false;
                    } else {
                        documentationRepositoryType.options[i].disabled = true;
                    }
                }
            } else {
                documentationRepositoryType.removeAttribute('readonly');
                for(i = 0; i < documentationRepositoryType.options.length; i++) {
                    documentationRepositoryType.options[i].disabled = false
                }
            }
        });
    }

    var discordWebhookType = document.getElementById('discord_webhook_type');
    if (discordWebhookType) {
        discordWebhookType.addEventListener('change', function() {
            if (discordWebhookType.options[discordWebhookType.selectedIndex].value === "3") {
                document.getElementById('discord_webhook_loglevel').disabled = false;
                document.getElementById('discord_webhook_loglevel').style.display = null;
                document.getElementById('discord_webhook_loglevel_label').style.display = null;
                document.getElementById('log_level_info_text').style.display = null;
            } else {
                document.getElementById('discord_webhook_loglevel').disabled = true;
                document.getElementById('discord_webhook_loglevel').style.display = 'none';
                document.getElementById('discord_webhook_loglevel_label').style.display = 'none';
                document.getElementById('log_level_info_text').style.display = 'none';
            }
        });
        if (discordWebhookType.options[discordWebhookType.selectedIndex].value !== "3") {
            document.getElementById('discord_webhook_loglevel').disabled = true;
            document.getElementById('discord_webhook_loglevel').style.display = 'none';
            document.getElementById('discord_webhook_loglevel_label').style.display = 'none';
            document.getElementById('log_level_info_text').style.display = 'none';
        }
    }

});
