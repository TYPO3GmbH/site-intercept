package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.credentials.SharedCredentialsIdentifier;
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
import com.atlassian.bamboo.specs.builders.task.*;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsWebRootResourcesSpec extends AbstractSpec {
    private static String planName = "Docs - Web Root Resources";
    private static String planKey = "DWR";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsRedirectsSpec planSpec = new DocsRedirectsSpec();
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
                                .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -e\nset -x\n\n# clone docs homepage repo and checkout master branch\nmkdir project\ngit clone https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git project\ncd project && git checkout master"),
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
                        .tasks(new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@srv007.typo3.com"))
                                .description("mkdir")
                                .host("srv007.typo3.com")
                                .username("prod.docs.typo3.com")
                                .command("set -e\r\nset -x\r\n\r\nmkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                            new ScpTask()
                                .description("copy result")
                                .host("srv007.typo3.com")
                                .username("prod.docs.typo3.com")
                                .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@srv007.typo3.com"))
                                .fromArtifact(new ArtifactItem()
                                    .artifact("resources.tgz")),
                            new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@srv007.typo3.com"))
                                .description("unpack and publish docs")
                                .host("srv007.typo3.com")
                                .username("prod.docs.typo3.com")
                                .command("set -e\r\nset -x\r\n\r\nsource_dir=\"/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}/\"\r\ncd ${source_dir} || exit 1\r\n\r\ntar xf resources.tgz\r\n\r\ntarget_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/\"\r\n\r\ncd ${target_dir} || exit 1\r\n\r\n# Move the single resource files and directories\r\nrm -f robots.txt\r\nmv ${source_dir}project/WebRootResources/robots.txt .\r\n\r\nrm -f favicon.ico\r\nmv ${source_dir}project/WebRootResources/favicon.ico .\r\n\r\nrm -rf js\r\nmv ${source_dir}project/WebRootResources/js .\r\n\r\nrm -rf t3SphinxThemeRtd\r\nmv ${source_dir}project/WebRootResources/t3SphinxThemeRtd .\r\n\r\n# And clean the temp deployment dir afterwards\r\nrm -rf ${source_dir}"))
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
