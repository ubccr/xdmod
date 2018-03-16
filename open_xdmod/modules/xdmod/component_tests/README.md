# XDMoD Component Tests

## Running Tests

1. Set `TEST_ENV` based on the tests that you would like to perform.  The default is to run the Open
XDMoD tests (e.g., `TEST_ENV=xdmod`).
2. Execute the tests: `./runtests.sh`

## Environment Variables

The test environment is selected using the `TEST_ENV` environment variable. Supported values are
- `xdmod` (this is the default if not set)
- `xdmod-xsede`
- `xdmod-supremm`

The value of this variable determines the directory in the `xdmod-test-artifacts` repo that is used
for reading test files and may also affect which tests are run. For example, XSEDE tests do not need
to be run when testing Open XDMoD. This allows the same tests to be executed using different inputs
and different expected outputs. The `xdmod-test-artifacts` repo must contain a directory matching
the value of `TEST_ENV`.

## Test Groups

Depending on the XDMoD version being tested, not all tests are necessary and some tests may not run.
For example, the Open XDMoD shredder tests will not work when run against the XSEDE docker image
because the XSEDE version does not include the necessary `mod_shredder` database (XSEDE does not use
this infrastructure but pulls from the XDCDB). We use PHPUnit [test
groups](https://phpunit.de/manual/current/en/appendixes.annotations.html#appendixes.annotations.group)
to organize tests into logical groups that can be selected based on the version being tested. Groups
may be added to a test class or individual test methods. Multiple groups may be added to the same
target.

Supported groups are:
- `@group XDMoD-common` General Open XDMoD tests relevant to all modules. Note that the input and expected
   output files may be controlled using the `TEST_ENV` environment variable.
- `@group XDMoD-shredder` Open XDMoD tests specific to shredding data from local sources
- `@group XDMoD-hpcdb` Open XDMoD tests specific to bringing shredded data into the data warehouse
- `@group XDMoD-xsede` Tests specific to the XSEDE module
- `@group XDMoD-supremm` Tests specific to the SUPReMM module

## Test Suites

PHPUnit [test suites](https://phpunit.de/manual/current/en/organizing-tests.html) are named tests
composed of groups or directories. These allow us to run tests specific to the version of XDMoD that
we are testing as well as tests common to all groups. Test suites are defined in `phpunit.xml.dist`
located in the test directory.  For example:

```
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         ...
         verbose="true">
    <testsuite name="xdmod-xsede">
        <directory> lib </directory>
        <exclude> lib/SlurmHelperTest.php </exclude>
    </testsuite>
</phpunit>
```
