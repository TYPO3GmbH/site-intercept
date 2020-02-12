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
import com.atlassian.bamboo.specs.builders.trigger.ScheduledTrigger;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsRenderingSurfMasterSpec extends AbstractSpec {
    private static String planName = "Docs - Rendering Surf master";
    private static String planKey = "DRSM";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsRedirectsSpec planSpec = new DocsRedirectsSpec();
        bambooServer.publish(planSpec.plan());
        bambooServer.publish(planSpec.getDefaultPlanPermissions(projectKey, planKey));
    }

    public Plan plan() {
        return new Plan(project(), planName, planKey)
            .pluginConfigurations(new ConcurrentBuilds(),
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                        .put("custom.buildExpiryConfig", buildExpiryConfig())
                        .build()))
            .stages(new Stage("Render Stage")
                    .jobs(new Job("Default Job",
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
                                .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -e\nset -x\n\n# clone repo to project/ and checkout requested branch / tag\nmkdir project\ngit clone https://github.com/TYPO3/Surf.git project\ncd project && git checkout master\ncd ..\n\ntouch project/jobfile.json\ncat << EOF > project/jobfile.json\n{\n    \"Overrides_cfg\": {\n        \"html_theme_options\": {\n            \"docstypo3org\": \"yes\",\n            \"add_piwik\": \"yes\",\n            \"show_legalinfo\": \"yes\"\n        }\n    }\n}\nEOF\n\nfunction renderDocs() {\n    docker run \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n        --rm \\\n        --entrypoint bash \\\n        t3docs/render-documentation:v2.4.0 \\\n        -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n}\nmkdir -p RenderedDocumentation\nmkdir -p FinalDocumentation\n\n# main render call - will render main documentation and localizations\nrenderDocs\n\n# Move files to target dir, including dot files\n(shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation)\n# Remove the directory, all content has been moved\nrm -rf RenderedDocumentation"),
                            new CommandTask()
                                .description("archive rendered docs")
                                .executable("tar")
                                .argument("cfz docs.tgz FinalDocumentation"))
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
                                .description("Unpack and publish docs")
                                .command("set -e\r\nset -x\r\n\r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\nmkdir documentation_result\r\ntar xf docs.tgz -C documentation_result\r\n\r\ntarget_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/other/typo3/surf/master/en-us\"\r\n\r\necho \"Deploying to ${target_dir}\"\r\n\r\nmkdir -p $target_dir\r\nrm -rf $target_dir/*\r\n\r\nmv documentation_result/FinalDocumentation/* $target_dir\r\n\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"))
                        .requirements(new Requirement("system.hasDocker")
                                .matchValue("1.0")
                                .matchType(Requirement.MatchType.EQUALS),
                            new Requirement("system.builder.command.tar"))
                        .artifactSubscriptions(new ArtifactSubscription()
                            .artifact("docs.tgz"))
                        .cleanWorkingDirectory(true)))
            .triggers(new ScheduledTrigger()
                .cronExpression("0 0 2 ? * *"))
            .variables(new Variable("BUILD_INFORMATION_FILE",
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
}
