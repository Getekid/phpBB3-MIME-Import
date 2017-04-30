<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text"/>
    <!-- Blockquotes -->
    <xsl:template match="blockquote">[quote]<xsl:apply-templates/>[/quote]</xsl:template>
    <!-- Lists -->
    <xsl:template match="ul">[list]<xsl:apply-templates/>[/list]</xsl:template>
    <xsl:template match="ol">[list=1]<xsl:apply-templates/>[/list]</xsl:template>
    <xsl:template match="li">[*]<xsl:apply-templates/></xsl:template>
    <!-- Text Format -->
    <xsl:template match="b|strong">[b]<xsl:apply-templates/>[/b]</xsl:template>
    <xsl:template match="i|em">[i]<xsl:apply-templates/>[/i]</xsl:template>
    <xsl:template match="u">[u]<xsl:apply-templates/>[/u]</xsl:template>
    <!-- Links -->
    <xsl:template match="a">
        <xsl:choose>
            <xsl:when test="substring(@href, 1, 7)='http://' or substring(@href, 1, 8)='https://'">[url="<xsl:value-of select="@href"/>"]<xsl:apply-templates/>[/url]</xsl:when>
            <xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- Images and Attachments -->
    <xsl:template match="img">
        <xsl:choose>
            <xsl:when test="substring(@src, 1, 7)='http://' or substring(@src, 1, 8)='https://'">[img]<xsl:value-of select="@src"/>[/img]</xsl:when>
            <xsl:when test="substring(@src, 1, 4)='cid:'">[attachment=<xsl:value-of select="substring(@src, 5)"/>][/attachment]</xsl:when>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>
