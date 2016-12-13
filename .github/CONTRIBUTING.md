# How to Contribute

Third-party patches are essential for keeping XDMoD great. There are a
few guidelines that we need contributors to follow so that we can have a
chance of keeping on top of things.

## Getting Started

* Contact us about the feature or bug fix you intend to work on before you start.
  * The change may be something we are already working on internally, or we
    may have advice on how best to proceed.
  * If working on a bug fix, include:
    * Steps to reproduce the bug
    * The earliest version that you know has the bug
  * We can be **publicly** reached via:
    * The issues tab of this repository
    * The XDMoD mailing list (see [the Open XDMoD support page][support])
  * We can be **privately** reached via:
    * The XDMoD support email address (see [the Open XDMoD support page][support])
* Once you have the go-ahead, fork the repository on GitHub and make the
  changes there.

## Making Changes

* Create a topic branch from where you want to base your work.
  * Base your work on the oldest version of XDMoD you want to change. If you
    want to fix a bug in XDMoD 6.5 and newer, you would create a topic branch
    on `xdmod6.5` (`git checkout -b fix/my_contribution xdmod6.5`).
* Make commits of logical units. ([Squash](https://www.youtube.com/watch?v=qh9KtjfjzCU)
  your commits before submission if necessary.)
* Check for unnecessary whitespace with `git diff --check` before committing.

## Style Guidelines (linting)

* If there exists a linter config file for some aspect of the project (e.g. `.eslintrc.json`, `.editorconfig`, `.remarkrc`), please use the associated linter with the config.
* If there is no applicable linter or linter config when editing a file, try to stick with its current style.
* Make sure that you fix any errors found by all configured linters (e.g. remark-lint, ESLint, phpcs).
* If an error or warning can't be fixed, please add comments to the pull/commit explaining why.
* We realize that all code, including existing code, might not pass the linters (as configured).
    * If you want to fix stylistic errors for an entire file that you're working on (which is appreciated!), fix it in a separate commit before starting on any functional code changes. This makes it easier to see the functional changes.
    * Please do not submit changes that are _purely_ style fixes. While we would love to see the code get tidied up, every change introduced increases the chance of conflicts for someone else's work in progress.

## Submitting Changes

* Make sure you have followed the style guidelines.
* Push your changes to a topic branch in your fork of the repository.
* All relevant documentation should be added to or updated as part of your pull request.
* Submit a pull request to the repository.
* After feedback has been given, we expect responses (comments and/or commits) within two weeks. After two weeks of inactivity, we may close the pull request.

[support]: http://open.xdmod.org/support.html
