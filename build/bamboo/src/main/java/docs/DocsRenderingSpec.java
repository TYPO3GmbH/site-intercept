package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.AtlassianModule;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.notification.AnyNotificationRecipient;
import com.atlassian.bamboo.specs.api.builders.notification.Notification;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.ConcurrentBuilds;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.task.ArtifactItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsRenderingSpec extends AbstractSpec {
    private static String planName = "Docs - Rendering";
    private static String planKey = "DR";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsRenderingSpec planSpec = new DocsRenderingSpec();
        bambooServer.publish(planSpec.plan());
        bambooServer.publish(planSpec.getDefaultPlanPermissions(projectKey, planKey));
    }

    public Plan plan() {
        return new Plan(project(), planName, planKey)
            .description("Documentation main rendering chain")
            .pluginConfigurations(new ConcurrentBuilds()
                    .useSystemWideDefault(false)
                    .maximumNumberOfConcurrentBuilds(400),
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                        .put("custom.buildExpiryConfig", buildExpiryConfig())
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
                        .tasks(getAuthenticatedSshTask()
                                .description("mkdir")
                                .command("set -e\r\nset -x\r\n\r\nmkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                            getAuthenticatedScpTask()
                                .description("copy result")
                                .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                .fromArtifact(new ArtifactItem()
                                    .artifact("docs.tgz")),
                            getAuthenticatedSshTask()
                                .description("unpack and publish docs")
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
    }

    /**
     * @return
     */
    private String getInlineBodyContent() {
        final String inlineBody = "if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n"
            + "bash \"$0\" \"$@\"\n"
            + "exit \"$?\"\n"
            + "fi\n\n"
            + "set -e\n"
            + "set -x\n\n"
            + "# fetch build information file and source it\n"
            + "curl https://intercept.typo3.com/${bamboo_BUILD_INFORMATION_FILE} --output deployment_infos.sh\n"
            + "source deployment_infos.sh || (echo \"No valid deployment_infos.sh file found\"; exit 1)\n\n"
            + "# clone repo to project/ and checkout requested branch / tag\n"
            + "mkdir project\n"
            + "git clone ${repository_url} project\n"
            + "cd project && git checkout ${source_branch}\n"
            + "cd ..\n\n"
            + createJobFile()
            + "function renderDocs() {\n"
            + "    docker run \\\n"
            + "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
            + "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n"
            + "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
            + "        --rm \\\n"
            + "        --entrypoint bash \\\n"
            + "        t3docs/render-documentation:v2.4.0 \\\n"
            + "        -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n"
            + "}\n"
            + "mkdir -p RenderedDocumentation\nmkdir -p FinalDocumentation\n\n"
            + "# main render call - will render main documentation and localizations\n"
            + "renderDocs\n\n"
            + "# test if rendering failed for whatever reason\n"
            + "ls RenderedDocumentation/Result || exit 1\n\n"
            + "# if a result has been rendered for the main directory, we treat that as the 'en_us' version\n"
            + "if [ -d RenderedDocumentation/Result/project/0.0.0/ ]; then\n"
            + "        echo \"Handling main doc result as en-us version\"\n"
            + "        mkdir FinalDocumentation/en-us\n"
            + "        # Move en-us files to target dir, including dot files\n"
            + "        (shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation/en-us)\n"
            + "        # evil hack to get rid of hardcoded docs.typo3.org domain name in version selector js side\n"
            + "        # not needed with replace_static_in_html at the moment\n"
            + "        # sed -i 's%https://docs.typo3.org%%' FinalDocumentation/en-us/_static/js/theme.js\n"
            + "        # Remove the directory, all content has been moved\n"
            + "        rmdir RenderedDocumentation/Result/project/0.0.0/\n"
            + "        # Remove a possibly existing Localization.en_us directory, if it exists\n"
            + "        rm -rf RenderedDocumentation/Result/project/en-us/\n"
            + "fi\n\n"
            + "# now see if other localization versions have been rendered. if so, move them to FinalDocumentation/, too\n"
            + "if [ \"$(ls -A RenderedDocumentation/Result/project/)\" ]; then\n"
            + "    for LOCALIZATIONDIR in RenderedDocumentation/Result/project/*; do\n"
            + "            LOCALIZATION=`basename $LOCALIZATIONDIR`\n"
            + "            echo \"Handling localized documentation version ${LOCALIZATION:?Localization could not be determined}\"\n"
            + "            mkdir FinalDocumentation/${LOCALIZATION}\n"
            + "            (shopt -s dotglob; mv ${LOCALIZATIONDIR}/0.0.0/* FinalDocumentation/${LOCALIZATION})\n"
            + "            # Remove the localization dir, it should be empty now\n"
            + "            rmdir ${LOCALIZATIONDIR}/0.0.0/\n"
            + "            rmdir ${LOCALIZATIONDIR}\n"
            + "            # evil hack to get rid of hardcoded docs.typo3.org domain name in version selector js side\n"
            + "            # not needed with replace_static_in_html at the moment\n"
            + "            # sed -i 's%https://docs.typo3.org%%' FinalDocumentation/${LOCALIZATION}/_static/js/theme.js\n"
            + "    done\n"
            + "fi\n\n"
            + "rm -rf RenderedDocumentation";
        return inlineBody;
    }

    private String createJobFile() {
        return "touch project/jobfile.json\n"
            + "cat << EOF > project/jobfile.json\n"
            + "{\n"
            + "    \"Overrides_cfg\": {\n"
            + "        \"general\": {\n"
            + "            \"release\": \"$target_branch_directory\"\n"
            + "        },\n"
            + "        \"html_theme_options\": {\n"
            + "            \"docstypo3org\": \"yes\",\n"
            + "            \"add_piwik\": \"yes\",\n"
            + "            \"show_legalinfo\": \"yes\"\n"
            + "        }\n"
            + "    }\n"
            + "}\n"
            + "EOF\n\n";
    }
}
