package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.credentials.SharedCredentialsIdentifier;
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
import com.atlassian.bamboo.specs.api.builders.repository.VcsRepositoryIdentifier;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.task.ArtifactItem;
import com.atlassian.bamboo.specs.builders.task.CheckoutItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.ScpTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.SshTask;
import com.atlassian.bamboo.specs.builders.task.VcsCheckoutTask;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.model.trigger.RepositoryPollingTriggerProperties;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsExceptionCodes {

    public Plan plan() {
        final Plan plan = new Plan(new Project()
                .key(new BambooKey("CORE"))
                .name("TYPO3"),
                "Docs - Exception Codes",
                new BambooKey("DEC"))
                .pluginConfigurations(new ConcurrentBuilds(),
                        new AllOtherPluginsConfiguration()
                                .configuration(new MapBuilder()
                                        .put("custom.buildExpiryConfig", new MapBuilder()
                                                .put("period", "days")
                                                .put("labelsToKeep", "")
                                                .put("enabled", "true")
                                                .put("expiryTypeArtifact", "true")
                                                .put("duration", "1")
                                                .put("buildsToKeep", "")
                                                .build())
                                        .build()))
                .stages(new Stage("Render Stage")
                                .jobs(new Job("Render",
                                        new BambooKey("JOB1"))
                                        .pluginConfigurations(new AllOtherPluginsConfiguration()
                                                .configuration(new MapBuilder()
                                                        .put("custom", new MapBuilder()
                                                                .put("auto", new MapBuilder()
                                                                        .put("label", "")
                                                                        .put("regex", "")
                                                                        .build())
                                                                .put("clover", new MapBuilder()
                                                                        .put("useLocalLicenseKey", "true")
                                                                        .put("path", "")
                                                                        .put("license", "")
                                                                        .build())
                                                                .put("buildHangingConfig.enabled", "false")
                                                                .put("ncover.path", "")
                                                                .build())
                                                        .build()))
                                        .artifacts(new Artifact()
                                                .name("docs.tgz")
                                                .copyPattern("docs.tgz")
                                                .shared(true)
                                                .required(true))
                                        .tasks(new VcsCheckoutTask()
                                                        .checkoutItems(new CheckoutItem().defaultRepository()
                                                                        .path("master"),
                                                                new CheckoutItem()
                                                                        .repository(new VcsRepositoryIdentifier()
                                                                                .name("github TYPO3 TYPO3.CMS 10.4"))
                                                                        .path("10"),
                                                                new CheckoutItem()
                                                                        .repository(new VcsRepositoryIdentifier()
                                                                                .name("github TYPO3 TYPO3.CMS 9.5"))
                                                                        .path("9")),
                                                new ScriptTask()
                                                        .description("Render Documentation")
                                                        .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -e\nset -x\n\nmkdir exceptions\n\ncd master\ntag=$(git describe --tags --abbrev=0)\n./Build/Scripts/duplicateExceptionCodeCheck.sh -p > exceptions-$tag.json\nmv exceptions-$tag.json ../exceptions/.\n\ncd ../10\ntag=$(git describe --tags --abbrev=0)\n./Build/Scripts/duplicateExceptionCodeCheck.sh -p > exceptions-$tag.json\nmv exceptions-$tag.json ../exceptions/.\n\ncd ../9\ntag=$(git describe --tags --abbrev=0)\n./Build/Scripts/duplicateExceptionCodeCheck.sh -p > exceptions-$tag.json\nmv exceptions-$tag.json ../exceptions/."),
                                                new CommandTask()
                                                        .description("archive result")
                                                        .executable("tar")
                                                        .argument("cfz docs.tgz  exceptions"))
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
                                                                        .put("label", "")
                                                                        .put("regex", "")
                                                                        .build())
                                                                .put("clover", new MapBuilder()
                                                                        .put("useLocalLicenseKey", "true")
                                                                        .put("path", "")
                                                                        .put("license", "")
                                                                        .build())
                                                                .put("buildHangingConfig.enabled", "false")
                                                                .put("ncover.path", "")
                                                                .build())
                                                        .build()))
                                        .tasks(new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@prod.docs.typo3.com"))
                                                        .description("mkdir")
                                                        .host("prod.docs.typo3.com")
                                                        .username("prod.docs.typo3.com")
                                                        .command("set -e\r\nset -x\r\n\r\nmkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                                                new ScpTask()
                                                        .description("copy result")
                                                        .host("prod.docs.typo3.com")
                                                        .username("prod.docs.typo3.com")
                                                        .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                                        .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@prod.docs.typo3.com"))
                                                        .fromArtifact(new ArtifactItem()
                                                                .artifact("docs.tgz")),
                                                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@prod.docs.typo3.com"))
                                                        .description("unpack and publish docs")
                                                        .host("prod.docs.typo3.com")
                                                        .username("prod.docs.typo3.com")
                                                        .command("set -e\r\nset -x\r\n\r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\nmkdir result\r\ntar xf docs.tgz -C result\r\n\r\ntarget_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/typo3cms/exceptions/app/packages/exception-pages/res/exceptions/\"\r\n\r\necho \"Deploying to ${target_dir}\"\r\n\r\nmv -f result/exceptions/* $target_dir\r\n\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\n/srv/vhosts/prod.docs.typo3.com/site/Web/typo3cms/exceptions/app/vendor/bin/merge-exception-code-files"))
                                        .requirements(new Requirement("system.hasDocker")
                                                        .matchValue("1.0")
                                                        .matchType(Requirement.MatchType.EQUALS),
                                                new Requirement("system.builder.command.tar"))
                                        .artifactSubscriptions(new ArtifactSubscription()
                                                .artifact("docs.tgz"))
                                        .cleanWorkingDirectory(true)))
                .linkedRepositories("github TYPO3 TYPO3.CMS",
                        "github TYPO3 TYPO3.CMS 10.4",
                        "github TYPO3 TYPO3.CMS 9.5")

                .triggers(new RepositoryPollingTrigger()
                        .pollWithCronExpression("0 0 0 ? * *")
                        .withPollType(RepositoryPollingTriggerProperties.PollType.CRON))
                .variables(new Variable("GITHUB_PASSWORD",
                                "BAMSCRT@0@0@AvO1niM6ChDoj2slFgi7JAn0IESExwHYSZFFjRuqoAAsdaeSHJpA2APKoKmiFwrg"),
                        new Variable("GITHUB_USERNAME",
                                "typo3-documentation-team"),
                        new Variable("VERSION_NUMBER",
                                "master"))
                .planBranchManagement(new PlanBranchManagement()
                        .delete(new BranchCleanup())
                        .notificationForCommitters())
                .forceStopHungBuilds();
        return plan;
    }

    public PlanPermissions planPermission() {
        final PlanPermissions planPermission = new PlanPermissions(new PlanIdentifier("CORE", "DEC"))
                .permissions(new Permissions()
                        .userPermissions("susanne.moog", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.CLONE, PermissionType.BUILD, PermissionType.EDIT)
                        .groupPermissions("t3g-team-dev", PermissionType.BUILD, PermissionType.CLONE, PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT)
                        .loggedInUserPermissions(PermissionType.VIEW)
                        .anonymousUserPermissionView());
        return planPermission;
    }

    public static void main(String... argv) {
        //By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer("https://bamboo.typo3.com");
        final DocsExceptionCodes planSpec = new DocsExceptionCodes();

        final Plan plan = planSpec.plan();
        bambooServer.publish(plan);

        final PlanPermissions planPermission = planSpec.planPermission();
        bambooServer.publish(planPermission);
    }
}
