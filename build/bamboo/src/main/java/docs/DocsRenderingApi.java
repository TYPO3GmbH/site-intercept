import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.BambooOid;
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
import com.atlassian.bamboo.specs.builders.trigger.ScheduledTrigger;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class PlanSpec {

    public Plan plan() {
        final Plan plan = new Plan(new Project()
                .key(new BambooKey("CORE"))
                .name("TYPO3"),
            "Docs - API Rendering",
            new BambooKey("DAR"))
            .pluginConfigurations(new ConcurrentBuilds(),
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                            .put("custom.buildExpiryConfig", new MapBuilder()
                                .put("duration", "1")
                                .put("period", "days")
                                .put("labelsToKeep", "")
                                .put("maximumBuildsToKeep", "")
                                .put("buildsToKeep", "")
                                .put("enabled", "true")
                                .put("expiryTypeArtifact", "true")
                                .build())
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
                                    .name("output")
                                    .copyPattern("output.tgz")
                                    .shared(true)
                                    .required(true),
                                new Artifact()
                                    .name("output10")
                                    .copyPattern("output10.tgz")
                                    .shared(true)
                                    .required(true),
                                new Artifact()
                                    .name("output9")
                                    .copyPattern("output9.tgz")
                                    .shared(true)
                                    .required(true))
                            .tasks(new VcsCheckoutTask()
                                    .description("TYPO3 CMS")
                                    .checkoutItems(new CheckoutItem().defaultRepository()
                                            .path("core")),
                                new VcsCheckoutTask()
                                    .description("Checkout 10.4")
                                    .checkoutItems(new CheckoutItem()
                                            .repository(new VcsRepositoryIdentifier()
                                                    .name("github TYPO3 TYPO3.CMS 10.4"))
                                            .path("core10")),
                                new VcsCheckoutTask()
                                    .description("Checkout 9.5")
                                    .checkoutItems(new CheckoutItem()
                                            .repository(new VcsRepositoryIdentifier()
                                                    .name("github TYPO3 TYPO3.CMS 9.5"))
                                            .path("core9")),
                                new ScriptTask()
                                    .description("Generate all docs")
                                    .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -e\nset -x\n\nmkdir output\nmkdir output10\nmkdir output9\ngit clone https://github.com/TYPO3GmbH/doxygenapi.git doxygen\n\ncd doxygen\n# Render master\necho -e \"\\nPROJECT_NAME           = TYPO3 CMS\" >> Doxyfile\necho -e \"\\nPROJECT_NUMBER         = master\" >> Doxyfile\ncat Doxyfile\ndocker run \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/core:/mnt/doxygen \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/doxygen/:/mnt/doxyconf \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/output/:/mnt/output \\\n    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n    --rm \\\n     ghcr.io/typo3gmbh/doxygenapi /mnt/doxyconf/Doxyfile\n     \n# Render 10.4\necho -e \"\\nPROJECT_NUMBER         = 10.4\" >> Doxyfile\ncat Doxyfile\ndocker run \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/core:/mnt/doxygen \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/doxygen/:/mnt/doxyconf \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/output10/:/mnt/output \\\n    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n    --rm \\\n     ghcr.io/typo3gmbh/doxygenapi /mnt/doxyconf/Doxyfile\n     \n# Render 9.5\necho -e \"\\nPROJECT_NUMBER         = 9.5\" >> Doxyfile\ncat Doxyfile\ndocker run \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/core:/mnt/doxygen \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/doxygen/:/mnt/doxyconf \\\n    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/output9/:/mnt/output \\\n    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n    --rm \\\n     ghcr.io/typo3gmbh/doxygenapi /mnt/doxyconf/Doxyfile"),
                                new CommandTask()
                                    .description("archive master")
                                    .executable("tar")
                                    .argument("cfz output.tgz output"),
                                new CommandTask()
                                    .description("archive 10.4")
                                    .executable("tar")
                                    .argument("cfz output10.tgz output10"),
                                new CommandTask()
                                    .description("archive 9.5")
                                    .executable("tar")
                                    .argument("cfz output9.tgz output9"))
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
                            .tasks(new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.api.docs.typo3.com@srv007"))
                                    .description("mkdir")
                                    .host("srv007.typo3.com")
                                    .username("prod.api.docs.typo3.com")
                                    .command("set -e\r\nset -x\r\n\r\nmkdir -p /srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                                new ScpTask()
                                    .description("copy result")
                                    .host("srv007.typo3.com")
                                    .username("prod.api.docs.typo3.com")
                                    .toRemotePath("/srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                    .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.api.docs.typo3.com@srv007"))
                                    .fromArtifact(new ArtifactItem()
                                        .artifact("output")),
                                new ScpTask()
                                    .description("copy 10")
                                    .host("srv007.typo3.com")
                                    .username("prod.api.docs.typo3.com")
                                    .toRemotePath("/srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                    .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.api.docs.typo3.com@srv007"))
                                    .fromArtifact(new ArtifactItem()
                                        .artifact("output10")),
                                new ScpTask()
                                    .description("copy 9")
                                    .host("srv007.typo3.com")
                                    .username("prod.api.docs.typo3.com")
                                    .toRemotePath("/srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                    .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.api.docs.typo3.com@srv007"))
                                    .fromArtifact(new ArtifactItem()
                                        .artifact("output9")),
                                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.api.docs.typo3.com@srv007"))
                                    .description("unpack and publish docs")
                                    .host("srv007.typo3.com")
                                    .username("prod.api.docs.typo3.com")
                                    .command("set -e\r\nset -x\r\n\r\nsource_dir=\"/srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}/\"\r\ncd ${source_dir} || exit 1\r\n\r\n# master\r\nmkdir output\r\ntar xf output.tgz\r\ncd output/html/\r\ntarget_dir=\"/srv/vhosts/prod.api.docs.typo3.com/site/Web/master\"\r\nrm -Rf ${target_dir}\r\nmkdir ${target_dir}\r\nfind . -maxdepth 1 ! -path . -exec mv -t ../../../../site/Web/master/ {} +\r\n\r\n#10.4\r\ncd ${source_dir} \r\nmkdir output10\r\ntar xf output10.tgz\r\ncd output10/html/\r\ntarget_dir=\"/srv/vhosts/prod.api.docs.typo3.com/site/Web/10.4\"\r\nrm -Rf ${target_dir}\r\nmkdir ${target_dir}\r\nfind . -maxdepth 1 ! -path . -exec mv -t ../../../../site/Web/10.4/ {} +\r\n\r\n#9.5\r\ncd ${source_dir} \r\nmkdir output9\r\ntar xf output9.tgz\r\ncd output9/html/\r\ntarget_dir=\"/srv/vhosts/prod.api.docs.typo3.com/site/Web/9.5\"\r\nrm -Rf ${target_dir}\r\nmkdir ${target_dir}\r\nfind . -maxdepth 1 ! -path . -exec mv -t ../../../../site/Web/9.5/ {} +\r\n\r\n# And clean the temp deployment dir afterwards\r\n#rm -rf ${source_dir}"))
                            .requirements(new Requirement("system.hasDocker")
                                    .matchValue("1.0")
                                    .matchType(Requirement.MatchType.EQUALS),
                                new Requirement("system.builder.command.tar"))
                            .artifactSubscriptions(new ArtifactSubscription()
                                    .artifact("output9"),
                                new ArtifactSubscription()
                                    .artifact("output10"),
                                new ArtifactSubscription()
                                    .artifact("output"))
                            .cleanWorkingDirectory(true)))
            .linkedRepositories("github TYPO3 TYPO3.CMS",
                "github TYPO3 TYPO3.CMS 10.4",
                "github TYPO3 TYPO3.CMS 9.5")

            .triggers(new ScheduledTrigger()
                    .description("Nightly Build")
                    .cronExpression("0 30 3 ? * *"))
            .variables(new Variable("GITHUB_SECRET",
                    "BAMSCRT@0@0@Jc7lAPUauP5tBRLi37sAsBYgDsSVIlXz3dXFnbW6ebEaGitJJRNC5MUO4MsKyEEA"))
            .planBranchManagement(new PlanBranchManagement()
                    .delete(new BranchCleanup())
                    .notificationForCommitters())
            .forceStopHungBuilds();
        return plan;
    }

    public PlanPermissions planPermission() {
        final PlanPermissions planPermission = new PlanPermissions(new PlanIdentifier("CORE", "DAR"))
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
        final PlanSpec planSpec = new PlanSpec();

        final Plan plan = planSpec.plan();
        bambooServer.publish(plan);

        final PlanPermissions planPermission = planSpec.planPermission();
        bambooServer.publish(planPermission);
    }
}
