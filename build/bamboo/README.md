# Overview

These files configure the build jobs for [the TYPO3 Docs Rendering](https://bamboo.typo3.com/browse/CORE-DRS).

## Requirements

- Java 11 or higher
- Maven 3.6.x or higher
- Valid credentials stored in `.credentials`

    cp .credentials.example .credentials

## Installation (macOS)

    brew tap AdoptOpenJDK/openjdk
    brew cask install adoptopenjdk11
    brew install jenv
    # Add the code to your local shell environment as instructed
    jenv add /Library/Java/JavaVirtualMachines/adoptopenjdk-11.jdk/Contents/Home
    brew install maven

## Usage

- Create a `.credentials` file and add your bamboo credentials
- Verify that the build specs compile without errors: `mvn test`
- Now publish the new specs with this command: `mvn -Ppublish-specs`
