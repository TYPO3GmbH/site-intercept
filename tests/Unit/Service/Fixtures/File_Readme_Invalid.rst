|BuildStatus|_

.. |BuildStatus| image:: https://github.com/FriendsOfTYPO3/extension_builder/workflows/tests/badge.svg
   :alt: Build Status
.. _BuildStatus: https://github.com/FriendsOfTYPO3/extension_builder/actions

=====================================
TYPO3 Extension ``extension_builder``
=====================================

The Extension Builder helps you to develop a TYPO3 extension based on the
domain-driven MVC framework Extbase and the templating engine Fluid.

It provides a graphical modeler to define domain objects and their relations
as well as associated controllers with basic actions. It also provides a
properties form to define extension metadata, frontend plugins and backend
modules that use the previously defined controllers and actions. Finally, it
generates a basic extension that can be installed and further developed.

In addition to the *kickstart mode*, the Extension Builder also provides a
*roundtrip mode* that allows you to use the graphical editor
even after you have started making manual changes to the files.
In this mode, the Extension Builder retains the manual changes,
such as new methods, changed method bodies, comments and annotations,
even if you change the extension in the graphical editor.
