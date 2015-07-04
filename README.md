Introduction to biophp project
==============================
I'm not too savvy on wikis or group projects, but I think this is something that we can all work on.  Please contact me at lskatz+biophp at gmail.com if you would like to help modify the code.

Deprecated
========
There is another BioPHP that is much better than this one, labeled as Mark's biophp on [http://biophp.org].

About
=====
This is Lee Katz's set of PHP classes for bioinformatics. This is not related at all to the [http://biophp.org biophp.org] project. [http://esbg.biology.gatech.edu/lab/?page_id=58 Lee Katz] is a PhD student at The Georgia Institute of Technology, in Atlanta, GA, USA.

Functions so far include

* read/write fasta files
* read/write alignment files
* wrappers for
  * BLAST (and other NCBI tools)
  * Clustal
  * Muscle 

Installation
==============
To install, unpack all files into a biophp directory. At the beginning of any php file that you want to use biophp with,

    <? require_once "biophp/Bio.php"; ?>

Documentation
=============
Documentation is still lacking, but each file is heavily commented.
