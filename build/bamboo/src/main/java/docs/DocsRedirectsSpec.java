package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
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
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsRedirectsSpec extends AbstractSpec {
    private static String planName = "Docs - Redirects";
    private static String planKey = "DRD";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsRedirectsSpec planSpec = new DocsRedirectsSpec();
        bambooServer.publish(planSpec.plan());
        bambooServer.publish(planSpec.getDefaultPlanPermissions(projectKey, planKey));
    }

    public Plan plan() {
        return new Plan(project(), planName, planKey)
            .description("Build and Deploy redirects")
            .pluginConfigurations(new ConcurrentBuilds(),
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                        .put("custom.buildExpiryConfig", buildExpiryConfig())
                        .build()))
            .stages(new Stage("Default Stage")
                    .jobs(new Job("Collect Redirect Config",
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
                            .name("nginx.tgz")
                            .copyPattern("nginx.tgz")
                            .shared(true)
                            .required(true))
                        .tasks(new ScriptTask()
                                .description("Collect nginx files")
                                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                                .inlineBody("#!/bin/bash\n\nif [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -x\n\nls -la\n\nmkdir nginx\n\ncurl https://intercept.typo3.com/docs-redirects/${bamboo_REDIRECT_FILE} --output nginx/${bamboo_REDIRECT_FILE}\ncurl https://intercept.typo3.com/build/nginx/redirects.conf --output nginx/redirects.conf\n\necho \"done\"\nls -la"),
                            new CommandTask()
                                .description("archive nginx configs")
                                .executable("tar")
                                .argument("cfz nginx.tgz nginx"))
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
                                    .artifact("nginx.tgz")),
                            new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@srv007.typo3.com"))
                                .description("unpack and publish docs")
                                .host("srv007.typo3.com")
                                .username("prod.docs.typo3.com")
                                .command("set -e\r\nset -x\r\n\r\n# Create \r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\ntar xf nginx.tgz\r\n\r\n# Run Deployment script\r\n/srv/vhosts/prod.docs.typo3.com/home/bin/checkAndUpdateRedirectsConfiguration.sh ${bamboo.buildResultKey}\r\n\r\n# Cleanup your room\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"))
                        .requirements(new Requirement("system.hasDocker")
                                .matchValue("1.0")
                                .matchType(Requirement.MatchType.EQUALS),
                            new Requirement("system.builder.command.tar"))
                        .artifactSubscriptions(new ArtifactSubscription()
                            .artifact("nginx.tgz"))
                        .cleanWorkingDirectory(true)))
            .variables(new Variable("REDIRECT_FILE", ""),
                new Variable("REPOSITORY_URL", ""),
                new Variable("VERSION_NUMBER", "master"))
            .planBranchManagement(new PlanBranchManagement()
                .delete(new BranchCleanup())
                .notificationForCommitters())
            .forceStopHungBuilds();
    }
}
