<?xml version="1.0" encoding="UTF-8"?>
<!--                                                                                         -->
<!--  Example of POM that can be used to run a Sonar analysis on a PHP project with Maven.   -->
<!--  => more documentation at http://docs.codehaus.org/display/SONAR/Analyse+with+Maven     -->
<!--                                                                                         -->

<project xmlns="http://maven.apache.org/POM/4.0.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">
  <modelVersion>4.0.0</modelVersion>
  <groupId>%%GROUP_ID%%</groupId>
  <artifactId>%%ARTIFACT_ID%%</artifactId>
  <name>%%NAME%%</name>
  <version>1.0-SNAPSHOT</version>
  <!-- For the moment, specify pom as packaging for php projects -->
  <packaging>pom</packaging>

  <build>
    <!-- You cannot omit this one, because maven will implicitely add src/main/java
      to it -->
    <sourceDirectory>${basedir}/</sourceDirectory>
    <testSourceDirectory>${basedir}/Tests</testSourceDirectory>
    <plugins>
      <plugin>
        <groupId>org.codehaus.mojo</groupId>
        <artifactId>build-helper-maven-plugin</artifactId>
        <executions>
          <execution>
            <id>add-source</id>
            <phase>generate-sources</phase>
            <goals>
              <goal>add-source</goal>
            </goals>
            <configuration>
              <sources>
                <source>source/src</source>
                <!--<source>source/tests</source> -->
              </sources>
            </configuration>
          </execution>
        </executions>
      </plugin>
    </plugins>
  </build>

  <!-- some properties that you may want to change -->
  <properties>
    <sonar.language>php</sonar.language>

    <sonar.phpPmd.skip>false</sonar.phpPmd.skip>
    <sonar.phpCodesniffer.skip>false</sonar.phpCodesniffer.skip>
    <sonar.phpCodesniffer.argumentLine>vendor</sonar.phpCodesniffer.argumentLine>
    <sonar.phpDepend.skip>false</sonar.phpDepend.skip>
    <sonar.phpUnit.coverage.skip>false</sonar.phpUnit.coverage.skip>
    <sonar.phpUnit.skip>%%SKIP_PHPUNIT%%</sonar.phpUnit.skip>
    <sonar.phpUnit.coverage.skip>true</sonar.phpUnit.coverage.skip>

    <sonar.phpUnit.analyze.test.directory>false</sonar.phpUnit.analyze.test.directory>
    <sonar.phpcpd.skip>false</sonar.phpcpd.skip>
    <sonar.phpcpd.excludes>vendors,vendor</sonar.phpcpd.excludes>
    <sonar.phpDepend.ignore>vendors,vendor</sonar.phpDepend.ignore>
    <sonar.phpPmd.argumentLine>--exclude vendor,vendors</sonar.phpPmd.argumentLine>

    <!-- to enable mutliple source directories. The phase must patch with
      the build-helper-maven-plugin exectution phase -->
    <sonar.phase>generate-sources</sonar.phase>

  </properties>

</project>