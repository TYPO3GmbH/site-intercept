package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.AtlassianModule;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.BambooOid;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.notification.AnyNotificationRecipient;
import com.atlassian.bamboo.specs.api.builders.notification.Notification;
import com.atlassian.bamboo.specs.api.builders.permission.PermissionType;
import com.atlassian.bamboo.specs.api.builders.permission.Permissions;
import com.atlassian.bamboo.specs.api.builders.permission.PlanPermissions;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.PlanIdentifier;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.ConcurrentBuilds;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.task.ArtifactItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.ScpTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.SshTask;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsRenderingSpec {

    public Plan plan() {
        final Plan plan = new Plan(new Project()
                .oid(new BambooOid("18cp87dwzw7b6"))
                .key(new BambooKey("CORE"))
                .name("Core")
                .description("TYPO3 Core Builds"),
            "Docs - Rendering",
            new BambooKey("DR"))
            .oid(new BambooOid("18cfizsjs2vib"))
            .description("Documentation main rendering chain")
            .pluginConfigurations(new ConcurrentBuilds()
                    .useSystemWideDefault(false)
                    .maximumNumberOfConcurrentBuilds(400),
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                            .put("custom.buildExpiryConfig", new MapBuilder()
                                .put("duration", "1")
                                .put("period", "days")
                                .put("labelsToKeep", "")
                                .put("buildsToKeep", "")
                                .put("enabled", "true")
                                .put("expiryTypeArtifact", "true")
                                .build())
                            .build()))
            .stages(new Stage("Render Stage")
                    .jobs(new Job("Render",
                            new BambooKey("JOB1"))
                            .pluginConfigurations(new AllOtherPluginsConfiguration()
                                    .configuration(new MapBuilder()
                                            .put("custom", new MapBuilder()
                                                .put("auto", new MapBuilder()
                                                    .put("regex", "")
                                                    .put("label", "")
                                                    .build())
                                                .put("buildHangingConfig.enabled", "false")
                                                .put("ncover.path", "")
                                                .put("clover", new MapBuilder()
                                                    .put("path", "")
                                                    .put("license", "")
                                                    .put("useLocalLicenseKey", "true")
                                                    .build())
                                                .build())
                                            .build()))
                            .artifacts(new Artifact()
                                    .name("docs.tgz")
                                    .copyPattern("docs.tgz")
                                    .shared(true))
                            .tasks(new ScriptTask()
                                    .description("Render documentation")
                                    .inlineBody(getInlineBodyContent()),
                                new CommandTask()
                                    .description("archive rendered docs")
                                    .executable("tar")
                                    .argument("cfz docs.tgz FinalDocumentation deployment_infos.sh"))
                            .requirements(new Requirement("system.hasDocker")
                                    .matchValue("1.0")
                                    .matchType(Requirement.MatchType.EQUALS))
                            .cleanWorkingDirectory(true)),
                new Stage("Deploy Stage")
                    .jobs(new Job("Deploy",
                            new BambooKey("DEP"))
                            .pluginConfigurations(new AllOtherPluginsConfiguration()
                                    .configuration(new MapBuilder()
                                            .put("custom", new MapBuilder()
                                                .put("auto", new MapBuilder()
                                                    .put("regex", "")
                                                    .put("label", "")
                                                    .build())
                                                .put("buildHangingConfig.enabled", "false")
                                                .put("ncover.path", "")
                                                .put("clover", new MapBuilder()
                                                    .put("path", "")
                                                    .put("license", "")
                                                    .put("useLocalLicenseKey", "true")
                                                    .build())
                                                .build())
                                            .build()))
                            .tasks(new SshTask().authenticateWithKeyWithPassphrase("BAMSCRT@0@0@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", "BAMSCRT@0@0@oUXHLLxIhG+krd5vDgsSWg==")
                                    .description("mkdir")
                                    .host("srv007.typo3.com")
                                    .username("prod.docs.typo3.com")
                                    .command("set -e\r\nset -x\r\n\r\nmkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                                new ScpTask()
                                    .description("copy result")
                                    .host("srv007.typo3.com")
                                    .username("prod.docs.typo3.com")
                                    .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                    .authenticateWithKeyWithPassphrase("BAMSCRT@0@0@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", "BAMSCRT@0@0@oUXHLLxIhG+krd5vDgsSWg==")
                                    .fromArtifact(new ArtifactItem()
                                        .artifact("docs.tgz")),
                                new SshTask().authenticateWithKeyWithPassphrase("BAMSCRT@0@0@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", "BAMSCRT@0@0@oUXHLLxIhG+krd5vDgsSWg==")
                                    .description("unpack and publish docs")
                                    .host("srv007.typo3.com")
                                    .username("prod.docs.typo3.com")
                                    .command("set -e\r\nset -x\r\n\r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\nmkdir documentation_result\r\ntar xf docs.tgz -C documentation_result\r\n\r\nsource \"documentation_result/deployment_infos.sh\"\r\n\r\nweb_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web\"\r\ntarget_dir=\"${web_dir}/${type_short:?type_short must be set}/${vendor:?vendor must be set}/${name:?name must be set}/${target_branch_directory:?target_branch_directory must be set}\"\r\n\r\necho \"Deploying to $target_dir\"\r\n\r\nmkdir -p $target_dir\r\nrm -rf $target_dir/*\r\n\r\nmv documentation_result/FinalDocumentation/* $target_dir\r\n\r\n# Re-New symlinks in document root if homepage repo is deployed\r\n# And some other homepage specific tasks\r\nif [ \"${type_short}\" == \"h\" ] && [ \"${target_branch_directory}\" == \"master\" ]; then\r\n    cd $web_dir\r\n    # Remove existing links (on first level only!)\r\n    find . -maxdepth 1 -type l | while read line; do\r\n\t    rm -v \"$line\"\r\n    done\r\n    # link all files in deployed homepage repo to doc root\r\n    ls h/typo3/docs-homepage/master/en-us/ | while read file; do\r\n\t    ln -s \"h/typo3/docs-homepage/master/en-us/$file\"\r\n    done\r\n    # Copy js/extensions-search.js to Home/extensions-search.js to\r\n    # have this file parallel to Home/Extensions.html\r\n    cp js/extensions-search.js Home/extensions-search.js\r\n    # Touch the empty and unused system-exensions.js referenced by the extension search\r\n    touch Home/systemextensions.js\r\nfi\r\n\r\n# Fetch latest \"static\" extension list from intercept (this is a php route!)\r\n# and put it as Home/extensions.js to be used by Home/Extensions.html\r\ncurl https://intercept.typo3.com/assets/docs/extensions.js --output ${web_dir}/Home/extensions.js\r\n\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"))
                            .requirements(new Requirement("system.hasDocker")
                                    .matchValue("1.0")
                                    .matchType(Requirement.MatchType.EQUALS),
                                new Requirement("system.builder.command.tar"))
                            .artifactSubscriptions(new ArtifactSubscription()
                                    .artifact("docs.tgz"))
                            .cleanWorkingDirectory(true)))
            .variables(new Variable("BUILD_INFORMATION_FILE",
                    ""),
                new Variable("DIRECTORY",
                    ""),
                new Variable("PACKAGE",
                    ""))
            .planBranchManagement(new PlanBranchManagement()
                    .delete(new BranchCleanup())
                    .notificationForCommitters())
            .notifications(new Notification()
                    .type(new PlanCompletedNotification())
                    .recipients(new AnyNotificationRecipient(new AtlassianModule("com.atlassian.bamboo.plugins.bamboo-slack:recipient.slack"))
                            .recipientString("https://intercept.typo3.com/bamboo|||")))
            .forceStopHungBuilds();
        return plan;
    }

	/**
	 * @return
	 */
	private String getInlineBodyContent() {
		final String inlineBody = "if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n"
				+"bash \"$0\" \"$@\"\n"
				+"exit \"$?\"\n"
				+"fi\n\n"
				+"set -e\n"
				+"set -x\n\n"
				+"# fetch build information file and source it\n"
				+"curl https://intercept.typo3.com/${bamboo_BUILD_INFORMATION_FILE} --output deployment_infos.sh\n"
				+"source deployment_infos.sh || (echo \"No valid deployment_infos.sh file found\"; exit 1)\n\n"
				+"# clone repo to project/ and checkout requested branch / tag\n"
				+"mkdir project\n"
				+"git clone ${repository_url} project\n"
				+"cd project && git checkout ${source_branch}\n"
				+"cd ..\n\n"
				+createJobFile()
				+"function renderDocs() {\n"
				+"    docker run \\\n"
				+"        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
				+"        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n"
				+"        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
				+"        --rm \\\n"
				+"        --entrypoint bash \\\n"
				+"        t3docs/render-documentation\\\n"
				+"        -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n"
				+"}\n"
				+"mkdir -p RenderedDocumentation\nmkdir -p FinalDocumentation\n\n"
				+"# main render call - will render main documentation and localizations\n"
				+"renderDocs\n\n"
				+"# test if rendering failed for whatever reason\n"
				+"ls RenderedDocumentation/Result || exit 1\n\n"
				+"# if a result has been rendered for the main directory, we treat that as the 'en_us' version\n"
				+"if [ -d RenderedDocumentation/Result/project/0.0.0/ ]; then\n"
				+"        echo \"Handling main doc result as en-us version\"\n"
				+"        mkdir FinalDocumentation/en-us\n"
				+"        # Move en-us files to target dir, including dot files\n"
				+"        (shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation/en-us)\n"
				+"        # evil hack to get rid of hardcoded docs.typo3.org domain name in version selector js side\n"
				+"        # not needed with replace_static_in_html at the moment\n"
				+"        # sed -i 's%https://docs.typo3.org%%' FinalDocumentation/en-us/_static/js/theme.js\n"
				+"        # Remove the directory, all content has been moved\n"
				+"        rmdir RenderedDocumentation/Result/project/0.0.0/\n"
				+"        # Remove a possibly existing Localization.en_us directory, if it exists\n"
				+"        rm -rf RenderedDocumentation/Result/project/en-us/\n"
				+"fi\n\n"
				+"# now see if other localization versions have been rendered. if so, move them to FinalDocumentation/, too\n"
				+"if [ \"$(ls -A RenderedDocumentation/Result/project/)\" ]; then\n"
				+"    for LOCALIZATIONDIR in RenderedDocumentation/Result/project/*; do\n"
				+"            LOCALIZATION=`basename $LOCALIZATIONDIR`\n"
				+"            echo \"Handling localized documentation version ${LOCALIZATION:?Localization could not be determined}\"\n"
				+"            mkdir FinalDocumentation/${LOCALIZATION}\n"
				+"            (shopt -s dotglob; mv ${LOCALIZATIONDIR}/0.0.0/* FinalDocumentation/${LOCALIZATION})\n"
				+"            # Remove the localization dir, it should be empty now\n"
				+"            rmdir ${LOCALIZATIONDIR}/0.0.0/\n"
				+"            rmdir ${LOCALIZATIONDIR}\n"
				+"            # evil hack to get rid of hardcoded docs.typo3.org domain name in version selector js side\n"
				+"            # not needed with replace_static_in_html at the moment\n"
				+"            # sed -i 's%https://docs.typo3.org%%' FinalDocumentation/${LOCALIZATION}/_static/js/theme.js\n"
				+"    done\n"
				+"fi\n\n"
				+"rm -rf RenderedDocumentation";
		return inlineBody;
	}
	
	private String createJobFile() {
		return "touch project/jobfile.json\n"
				+"cat << EOF > project/jobfile.json\n"
				+"{\n"
				+"    \"Overrides_cfg\": {\n"
				+"        \"general\": {\n"
				+"            \"release\": \"$target_branch_directory\"\n"
				+"        },\n"
				+"        \"html_theme_options\": {\n"
				+"            \"docstypo3org\": \"yes\",\n"
				+"            \"add_piwik\": \"yes\",\n"
				+"            \"show_legalinfo\": \"yes\"\n"
				+"        }\n"
				+"    }\n"
				+"}\n"
				+"EOF\n\n";
	}

    public PlanPermissions planPermission() {
        final PlanPermissions planPermission = new PlanPermissions(new PlanIdentifier("CORE", "DR"))
            .permissions(new Permissions()
                    .userPermissions("christian.kuhn", PermissionType.EDIT, PermissionType.VIEW, PermissionType.ADMIN, PermissionType.CLONE, PermissionType.BUILD)
                    .userPermissions("daniel.siepmann", PermissionType.BUILD, PermissionType.VIEW, PermissionType.CLONE, PermissionType.ADMIN, PermissionType.EDIT)
                    .groupPermissions("TYPO3 GmbH", PermissionType.BUILD, PermissionType.VIEW, PermissionType.CLONE, PermissionType.ADMIN, PermissionType.EDIT)
                    .loggedInUserPermissions(PermissionType.VIEW)
                    .anonymousUserPermissionView());
        return planPermission;
    }

    public static void main(String... argv) {
        //By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer("https://bamboo.typo3.com");
        final DocsRenderingSpec planSpec = new DocsRenderingSpec();

        final Plan plan = planSpec.plan();
        bambooServer.publish(plan);

        final PlanPermissions planPermission = planSpec.planPermission();
        bambooServer.publish(planPermission);
    }
}
