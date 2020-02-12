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
public class DocsDeletionSpec extends AbstractSpec {
    private static String planName = "Docs - Deletion";
    private static String planKey = "DDEL";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsDeletionSpec planSpec = new DocsDeletionSpec();
        bambooServer.publish(planSpec.plan());
        bambooServer.publish(planSpec.getDefaultPlanPermissions(projectKey, planKey));
    }

    public Plan plan() {
        return new Plan(project(), planName, planKey)
            .description("Build and deploy documentation deletions")
            .pluginConfigurations(new ConcurrentBuilds(),
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                        .put("custom.buildExpiryConfig", buildExpiryConfig())
                        .build()))
            .stages(new Stage("Render Stage")
                    .jobs(new Job("Render",
                        new BambooKey("JOB1"))
                        .artifacts(new Artifact()
                            .name("docs.tgz")
                            .copyPattern("docs.tgz")
                            .shared(true))
                        .tasks(new ScriptTask()
                                .description("Download job description")
                                .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -e\nset -x\n\n# fetch build information file and source it\ncurl https://intercept.typo3.com/${bamboo_BUILD_INFORMATION_FILE} --output deployment_infos.sh\nsource deployment_infos.sh || (echo \"No valid deployment_infos.sh file found\"; exit 1)\n\n# Fetch \"static\" extension list\ncurl https://intercept.typo3.com/assets/docs/extensions.js --output extensions.js"),
                            new CommandTask()
                                .description("Archive files for artifact")
                                .executable("tar")
                                .argument("cfz docs.tgz deployment_infos.sh extensions.js"))
                        .requirements(new Requirement("system.hasDocker")
                            .matchValue("1.0")
                            .matchType(Requirement.MatchType.EQUALS))),
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
                                .command("set -e\r\nset -x\r\n\r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}/\r\n\r\nmkdir documentation_result\r\ntar xf docs.tgz -C documentation_result\r\n\r\nsource \"documentation_result/deployment_infos.sh\"\r\n\r\nweb_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web\"\r\ntarget_dir=\"${web_dir}/${type_short:?type_short must be set}/${vendor:?vendor must be set}/${name:?name must be set}/${target_branch_directory:?target_branch_directory must be set}\"\r\n\r\necho \"Deleting $target_dir\"\r\n\r\nrm -rf $target_dir\r\n\r\n# Move \"static\" extension list\r\nmv documentation_result/extensions.js ${web_dir}/Home/extensions.js\r\n\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}/"))
                        .requirements(new Requirement("system.hasDocker")
                                .matchValue("1.0")
                                .matchType(Requirement.MatchType.EQUALS),
                            new Requirement("system.builder.command.tar"))
                        .artifactSubscriptions(new ArtifactSubscription()
                            .artifact("docs.tgz"))
                        .cleanWorkingDirectory(true)))
            .linkedRepositories(linkedRepository)

            .variables(new Variable("BUILD_INFORMATION_FILE", ""))
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
