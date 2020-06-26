package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
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
import com.atlassian.bamboo.specs.builders.task.ArtifactItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsWebRootResourcesSpec extends AbstractSpec {
    private static String planName = "Docs - Web Root Resources";
    private static String planKey = "DWR";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsWebRootResourcesSpec planSpec = new DocsWebRootResourcesSpec();
        bambooServer.publish(planSpec.plan());
        bambooServer.publish(planSpec.getDefaultPlanPermissions(projectKey, planKey));
    }

    public Plan plan() {
        return new Plan(project(), planName, planKey)
            .description("Static web root resources from docs homepage repository deployed to docs server document root")
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
                            .name("resources.tgz")
                            .copyPattern("resources.tgz")
                            .shared(true))
                        .tasks(new ScriptTask()
                                .description("Clone docs homepage repo")
                                .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n"
                                        + "    bash \"$0\" \"$@\"\n"
                                        + "    exit \"$?\"\n"
                                        + "fi\n\n"
                                        + "set -e\n"
                                        + "set -x\n\n"
                                        + "# clone docs homepage repo and checkout master branch\n"
                                        + "mkdir project\n"
                                        + "git clone https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git project\n"
                                        + "cd project && git checkout master"
                                ),
                            new CommandTask()
                                .description("archive static resources")
                                .executable("tar")
                                .argument("cfz resources.tgz project/WebRootResources"))
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
                        .artifact("resources.tgz")),
                getAuthenticatedSshTask()
                    .description("unpack and publish docs")
                    .command("set -e\n"
                        + "set -x\n\n"
                        + "source_dir=\"/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}/\"\n"
                        + "cd ${source_dir} || exit 1\n\n"
                        + "tar xf resources.tgz\n\n"
                        + "target_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/\"\n\n"
                        + "cd ${target_dir} || exit 1\n\n"
                        + "# Move the single resource files and directories\n"
                        + "rm -f robots.txt\n"
                        + "mv ${source_dir}project/WebRootResources/robots.txt .\n\n"
                        + "rm -f favicon.ico\n"
                        + "mv ${source_dir}project/WebRootResources/favicon.ico .\n\n"
                        + "rm -rf js\n"
                        + "mv ${source_dir}project/WebRootResources/js .\n\n"
                        + "rm -rf t3SphinxThemeRtd\n"
                        + "mv ${source_dir}project/WebRootResources/t3SphinxThemeRtd .\n\n"
                        + "# And clean the temp deployment dir afterwards\nrm -rf ${source_dir}"
                )
            )
            .requirements(new Requirement("system.hasDocker")
                .matchValue("1.0")
                .matchType(Requirement.MatchType.EQUALS),
                new Requirement("system.builder.command.tar"))
            .artifactSubscriptions(new ArtifactSubscription()
                .artifact("resources.tgz"))
            .cleanWorkingDirectory(true)))
            .planBranchManagement(new PlanBranchManagement()
                .delete(new BranchCleanup())
                .notificationForCommitters())
            .forceStopHungBuilds();
    }
}
