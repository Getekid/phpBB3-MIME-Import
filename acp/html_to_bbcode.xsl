<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text"/>
    <xsl:template match="b|strong">[b]<xsl:apply-templates/>[/b]</xsl:template>
    <xsl:template match="i|em">[i]<xsl:apply-templates/>[/i]</xsl:template>
    <xsl:template match="u">[u]<xsl:apply-templates/>[/u]</xsl:template>
    <xsl:template match="a">[url="<xsl:value-of select="@href"/>"]<xsl:apply-templates/>[/url]</xsl:template>
</xsl:stylesheet>
