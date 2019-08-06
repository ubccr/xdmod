#SLURM format

This quick and dirty script was written due to an incident where old slurm files needed to be reingested.
The old slurm files were in an incompatible format from the current format so this updated those files and fixed some "issues" found along the way.

Since our files are actually stored as bz2 on the server a little bash work to loop over the files uncompress them and then run the node process to have it do its thing was done.

and example of the bash script is

bunzip2 -k /scratch/historical_slurm/2017/10/dumpstats-2017-10-07.txt.bz2
node parse.js 2017-10-07
rm -rf /scratch/historical_slurm/2017/10/dumpstats-2017-10-07.txt

**NOTE: this was only run for our specific incident make changes and pull requests as needed**
