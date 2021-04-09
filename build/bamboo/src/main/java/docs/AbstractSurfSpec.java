package docs;

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
import com.atlassian.bamboo.specs.util.MapBuilder;

abstract class AbstractSurfSpec extends AbstractSpec {
    protected Plan configurePlan(Plan plan, String version) {
        return plan.pluginConfigurations(new ConcurrentBuilds(),
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
                                .inlineBody(this.getInlineBodyContent()),
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
                                .command("set -e\n"
                                    + "set -x\n\n"
                                    + "mkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"
                                ),
                            getAuthenticatedScpTask()
                                .description("copy result")
                                .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                .fromArtifact(new ArtifactItem()
                                    .artifact("docs.tgz")),
                            getAuthenticatedSshTask()
                                .description("Unpack and publish docs")
                                .command("set -e\n"
                                    + "set -x\n\n"
                                    + "cd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\n\n"
                                    + "mkdir documentation_result\n"
                                    + "tar xf docs.tgz -C documentation_result\n\n"
                                    + "target_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/other/typo3/surf/${bamboo.VERSION_NUMBER}/en-us\"\n\n"
                                    + "echo \"Deploying to ${target_dir}\"\n\n"
                                    + "mkdir -p $target_dir\n"
                                    + "rm -rf $target_dir/*\n\n"
                                    + "mv documentation_result/FinalDocumentation/* $target_dir\n\n"
                                    + "rm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"
                                )
                        )
                        .requirements(new Requirement("system.hasDocker")
                                .matchValue("1.0")
                                .matchType(Requirement.MatchType.EQUALS),
                            new Requirement("system.builder.command.tar"))
                        .artifactSubscriptions(new ArtifactSubscription()
                            .artifact("docs.tgz"))
                        .cleanWorkingDirectory(true)))
            .triggers(new ScheduledTrigger()
                .cronExpression("0 0 2 ? * *"))
            .variables(new Variable("VERSION_NUMBER", version))
            .planBranchManagement(new PlanBranchManagement()
                .delete(new BranchCleanup())
                .notificationForCommitters())
            .notifications(new Notification()
                .type(new PlanCompletedNotification())
                .recipients(new AnyNotificationRecipient(new AtlassianModule("com.atlassian.bamboo.plugins.bamboo-slack:recipient.slack"))
                    .recipientString("https://intercept.typo3.com/bamboo|||")))
            .forceStopHungBuilds();
    }

    private String getInlineBodyContent() {
        final String inlineBody = "if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n"
            + "    bash \"$0\" \"$@\"\n"
            + "    exit \"$?\"\n"
            + "fi\n\n"
            + "set -e\n"
            + "set -x\n\n"
            + "# clone repo to project/ and checkout requested branch / tag\n"
            + "mkdir project\n"
            + "git clone https://github.com/TYPO3/Surf.git project\n"
            + "cd project && git checkout ${bamboo.VERSION_NUMBER}\n"
            + "cd ..\n\n"
            + createJobFile()
            + restoreModificationTime()
            + "function renderDocs() {\n"
            + "    docker run \\\n"
            + "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
            + "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n"
            + "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
            + "        --rm \\\n"
            + "        --entrypoint bash \\\n"
            + "        t3docs/render-documentation:v2.8.3 \\\n"
            + "        -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n"
            + "}\n"
            + "mkdir -p RenderedDocumentation\n"
            + "mkdir -p FinalDocumentation\n\n"
            + "# main render call - will render main documentation and localizations\n"
            + "renderDocs\n\n"
            + "# Move files to target dir, including dot files\n"
            + "(shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation)\n"
            + "# Remove the directory, all content has been moved\n"
            + "rm -rf RenderedDocumentation";
        return inlineBody;
    }

    protected String createJobFile() {
        return "touch project/jobfile.json\n"
            + "cat << EOF > project/jobfile.json\n"
            + "{\n"
            + "    \"Overrides_cfg\": {\n"
            + "        \"html_theme_options\": {\n"
            + "            \"docstypo3org\": \"yes\"\n"
            + "        }\n"
            + "    }\n"
            + "}\n"
            + "EOF\n\n";
    }
}
