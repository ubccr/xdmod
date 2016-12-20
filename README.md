# Open XDMoD

XDMoD (XD Metrics on Demand) is an NSF-funded open source tool designed to audit
and facilitate the utilization of the XSEDE cyberinfrastructure by providing a
wide range of metrics on XSEDE resources, including resource utilization,
resource performance, and impact on scholarship and research. The XDMoD
framework is designed to meet the following objectives:

  1. Provide the user community with a tool to manage their allocations and
     optimize their resource utilization,
  1. Provide operational staff with the ability to monitor and tune
     resource performance,
  1. Provide management with a tool to monitor utilization,
     user base, and performance of resources, and
  1. Provide metrics to help measure scientific impact.

While initially focused on the XSEDE program, Open XDMoD has been created to be
adaptable to any HPC environment.

For more information, including information about additional Open XDMoD
capabilities provided as optional modules, please visit
[the Open XDMoD website](http://open.xdmod.org).

## Modules for Open XDMoD

In addition to the analysis functionality provided by the main Open XDMoD
package, Open XDMoD also supports extending its capabilities through
modules. The XDMoD team currently supports the modules below.

### Application Kernels

This module enables analysis of "application kernels", which are regression
tests for the performance of an HPC system and the software that runs on it.
For more information, visit the Application Kernels Module
[website](http://appkernels.xdmod.org/ak-overview.html) and
[repository](https://github.com/ubccr/xdmod-appkernels).

### SUPReMM

This module enables analysis of individual and aggregate job performance using
hardware data from CPUs, memory, filesystems, network interfaces, and more.
For more information, visit the SUPReMM Module
[website](http://supremm.xdmod.org/supremm-overview.html) and
[repository](https://github.com/ubccr/xdmod-supremm).

## Installation

Prebuilt packages of Open XDMoD are available as
[releases on GitHub](https://github.com/ubccr/xdmod/releases). Packages for
Open XDMoD modules are available as releases in their respective repositories.

See [the installation instructions on the Open XDMoD website](http://open.xdmod.org/install.html)
for additional information.

## Contributing

Feedback is always welcome, and contributions are greatly appreciated!
Before getting started, please see
[our contributing instructions and guidelines](.github/CONTRIBUTING.md).

In short, the steps to take are:
  1. Fork any repositories for Open XDMoD or its modules that you wish to
     work on.
  1. Clone and set up the repositories on your local system
     (see "[Developing](#developing)", below).
  1. Develop your work and test it, ensuring your work follows
     [our contributing guidelines](.github/CONTRIBUTING.md).
  1. Push your work to your forks.
  1. Open pull requests for your work from your forks to the central
     repositories. The pull requests will then be reviewed by the XDMoD team.

## Developing

Development on Open XDMoD and its modules can be started using either
[Repo][] or Git. If you are unsure which to start with, try Repo, as it is
easy to transition from a Repo workflow to a pure Git workflow. If you
don't want to install yet another tool, using Git will work just fine.

Before starting with either, however, you will want to fork any repositories
you are interested in working on. Simply visit the repositories' pages on GitHub
and click the Fork button. Once you have finished working on a feature or bug
fix for a project, push the work to your fork and open a pull request against
the main repo for that project.

### Using Git

To get started on core Open XDMoD development, simply clone the Open XDMoD
repository.

To work on an Open XDMoD module, one option is to clone the module repository
directly inside of your Open XDMoD repository's `open_xdmod/modules` directory.
If your module repository is named with the `xdmod-` prefix, remove it from the
clone's directory name. Alternatively, you may clone the module repository
elsewhere and create a symbolic link to it from `open_xdmod/modules`.

For example, to work on your fork of the SUPReMM module using a direct clone,
run this command, substituting in your GitHub username and Open XDMoD repo
location:

```bash
git clone git@github.com:[username]/xdmod-supremm [xdmod_repo]/open_xdmod/modules/supremm
```

To work on your fork of the SUPReMM module using an external clone and a
symbolic link, run these commands, substituting in your GitHub username
and the relevant paths:

```bash
git clone git@github.com:[username]/xdmod-supremm [supremm_repo]
ln -s [supremm_repo] [xdmod_repo]/open_xdmod/modules/supremm
```

### Using Repo

To assist with initial setup and development across Open XDMoD and its modules,
we support the use of [Repo][], a tool built
by the Android development team to help manage multi-repository projects.
It can help you get started by setting up multiple repositories at once, and it
provides some convenience functions for working across repositories.
We supply a [Repo manifest repository][xdmod-repo-manifest]
that can be used to get started with Open XDMoD and first-party modules.

The steps below will get you started, but further documentation on using Repo
can be found [here](https://source.android.com/source/using-repo.html). At any
point, standard Git commands may be used in individual project directories,
as the directories are simply standard Git repositories.

To clone Open XDMoD and first-party modules, run the following commands,
substituting in the branch for the version you wish you base your work on
(e.g. `xdmod6.5`):

```bash
repo init -u git@github.com:ubccr/xdmod-repo-manifest -b [branch]
repo sync
```

Note that unlike `git clone`, Repo does not automatically create a local branch
tracking the initial branch that was checked out (although you can create these
branches manually, if you wish, by running `repo forall -c 'git checkout
[branch]'`).

To add forks of the various projects to all repositories at once, run this
command, substituting in your GitHub username:

```bash
repo forall -c 'git remote add origin git@github.com:[username]/$REPO_PROJECT'
```

To check that the above command worked correctly, you can run this command:

```bash
repo forall -c 'git fetch origin'
```

Now that the repositories have been set up, you can use standard Git commands
in each repository. Repo also provides some convenience functions for performing
tasks across all repositories. For example, `repo status` will display the
current branch and changes for all repositories. You can also use `repo forall`
to execute any shell command in all repositories.

#### Custom Modules with Repo

If you are working on custom modules for Open XDMoD, you can tell Repo where to
find them using one of two methods.

If you want to use a custom configuration in
multiple places, you can fork or clone the
[XDMoD Repo manifest repository][xdmod-repo-manifest] and apply modifications
there. (Note that a fork, like the source repo, will be public. If you wish to
keep your custom configs private, clone the main repo directly and treat the
clone as an independent repo. You can then push the clone to your own private
server, or you can just keep it local to your machine.) The existing entries for
various Open XDMoD modules can be used as templates for your custom modules. The
format of the Repo manifest file is described in greater detail
[here][repo-format]. Once you have made your changes and committed them,
run these commands to pull in your changes:

```bash
repo init -u [local_or_remote_path_to_manifest_repo] -b [branch_with_changes]
repo sync
```

If you just want to make some small changes locally, you can add local manifest
files to `.repo/local_manifests` that will extend the main manifest file being
used. More information about the manifest file format and local manifest files
may be found [here][repo-format]. Once you have made the desired changes, run
`repo sync` to pull the changes in.

## Building

### Dependencies

  - [PHP](https://secure.php.net/)
  - [Composer](https://getcomposer.org/)
  - [PEAR](https://pear.php.net/)
  - [PEAR Log Module](https://pear.php.net/package/Log/)
  - [Java Development Kit 7+](http://www.oracle.com/technetwork/java/javase/downloads/index.html)
  - [rpmbuild](http://rpm.org/)
    - This is only required if building RPMs.

**NOTE**: Modules for Open XDMoD may have their own build dependencies.

### Steps

The examples in the steps below apply to Open XDMoD, but similar procedures
may be followed to build modules for Open XDMoD as well. Simply ensure that
the modules (or symbolic links to the modules) are present in
`open_xdmod/modules` and do not have the `xdmod-` prefix. For example, to build
the SUPReMM module (which is stored in the repository `xdmod-supremm`),
clone it or create a symbolic link to it at `open_xdmod/modules/supremm`.

#### Source

  1. Change directory to the root of the Open XDMoD repository.
  1. Install Composer dependencies for Open XDMoD.
    - `composer install`
    - Depending on the versions of various software installed on your system,
      you may run into errors. See the [Building FAQ](#building-faq) below.
  1. Run the package builder script.
    - `open_xdmod/build_scripts/build_package.php --module xdmod`
    - To build Open XDMoD modules, substitute `xdmod` with the name of a
      module's directory within `open_xdmod/modules`.

The resulting tarball will be located in `open_xdmod/build`.

#### RPM

This procedure assumes your `rpmbuild` directory is `~/rpmbuild`. If it is not,
substitute accordingly.

  1. Change directory to the root of the Open XDMoD repository.
  1. If you have not already, create a source tarball using the steps in the
     Source section.
  1. Copy the source tarball to the `SOURCES` directory in your `rpmbuild`
     directory.
    - `cp open_xdmod/build/xdmod-x.y.z.tar.gz ~/rpmbuild/SOURCES`
  1. Extract the `.spec` file from the source tarball into the `SPECS` directory
     in your `rpmbuild` directory.
    - `tar -xOf ~/rpmbuild/SOURCES/xdmod-x.y.z.tar.gz xdmod-x.y.z/xdmod.spec >~/rpmbuild/SPECS/xdmod.spec`
  1. Run `rpmbuild`.
    - `rpmbuild -bb ~/rpmbuild/SPECS/xdmod.spec`
    - There may be warnings about files not being found or files being
      listed twice. These are likely benign.

The resulting RPM will be located in `~/rpmbuild/RPMS/noarch`.

### Building FAQ

#### Why is Composer unable to download some files?

Certain combinations of PHP and Composer do not handle redirects over HTTPS
correctly. To get things working, try one or more steps below.

  1. Update Composer to a newer version.
  1. Update PHP to a newer version.
  1. If the above did not work or is not feasible, you can globally disable
     HTTPS in Composer by running `composer config -g disable-tls true`. While
     disabling HTTPS is not recommended by the Composer developers or us, all
     dependencies downloaded using XDMoD's config files will be checked
     against checksums to help prevent against tampering.

#### Why can't Composer unzip Ext JS?

The ZIP file for the version of Ext JS being used contains multiple files
with the same path, and some ZIP programs and libraries do not handle this case
quietly. If Composer uses the system's `unzip` utility to unpack the ZIP file
and that version of `unzip` asks for input, Composer will error out.

Fortunately, PHP's ZIP library will work for this case. Unfortunately,
getting Composer to use the PHP library currently requires either modifying
Composer's code or hiding your system's `unzip` utility. You can do the latter
quickly by temporarily renaming `unzip` to something like `unzip-hidden`, then
changing the name back once Composer has completed installation. These solutions
aren't ones we're fans of, so if you have a better solution, please share!

## License

Open XDMoD is released under the GNU Lesser General Public License
("LGPL") Version 3.0.  See the [LICENSE](LICENSE) file for details.

Open XDMoD includes several libraries that are licensed separately.
See the [license page on the Open XDMoD website][license-page] for details.

### Non-Commercial Licenses

Some software products used by Open XDMoD are not free for commercial use.
See the [license page on the Open XDMoD website][license-page] for details.

[license-page]: http://open.xdmod.org/notices.html
[repo]: https://code.google.com/p/git-repo/
[repo-format]: https://gerrit.googlesource.com/git-repo/+/master/docs/manifest-format.txt
[xdmod-repo-manifest]: https://github.com/ubccr/xdmod-repo-manifest
