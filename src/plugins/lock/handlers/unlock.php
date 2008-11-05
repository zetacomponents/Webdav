<?php
/**
 * File containing the ezcWebdavLockUnlockRequestResponseHandler class.
 *
 * @package Webdav
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 *
 * @access private
 */
/**
 * Handler class for the UNLOCK request.
 * 
 * @package Webdav
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 *
 * @access private
 */
class ezcWebdavLockUnlockRequestResponseHandler extends ezcWebdavLockRequestResponseHandler
{
    /**
     * Callback if a response was generated for the handled request.
     *
     * If the method {@link receivedRequest()} returned null and the backend
     * has processed the request, the generated $response will be submitted to
     * this method. The handler may perform arbitrary operations, including the
     * manipulation of the $response. Again, this manipulation must happen most
     * carefully, to not influence other plugins
     *
     * The method might return null, to indicate that the submitted response
     * should be send, or a different instance of {@link ezcWebdavResponse} to
     * replace the one generated by the backend. This should only be done in
     * rare cases!
     * 
     * @param ezcWebdavResponse $response 
     * @return ezcWebdavResponse|null
     */
    public function generatedResponse( ezcWebdavResponse $response )
    {
        return null;
    }

    /**
     * Handles UNLOCK requests.
     *
     * This method determines the base of the lock determined by the Lock-Token
     * header of $request and releases the lock from all locked resources. In
     * case a lock null resource is beyond these, it will be deleted.
     * 
     * @param ezcWebdavUnlockRequest $request 
     * @return ezcWebdavResponse
     */
    public function receivedRequest( ezcWebdavRequest $request )
    {
        $srv = ezcWebdavServer::getInstance();

        $token = $request->getHeader( 'Lock-Token' );
        $authHeader = $request->getHeader( 'Authorization' );

        if ( $token === null )
        {
            // UNLOCK must have a lock token
            return new ezcWebdavErrorResponse( ezcWebdavResponse::STATUS_412 );
        }


        // Check permission

        if ( !$srv->isAuthorized(
                $request->requestUri,
                $authHeader,
                ezcWebdavAuthorizer::ACCESS_WRITE
             )
             || !$srv->auth->ownsLock( $authHeader->username, $token )
           )
        {
            return $srv->createUnauthorizedResponse(
                $request->requestUri,
                'Authorization failed.'
            );
        }

        // Find properties to determine lock base

        $propFindReq = new ezcWebdavPropFindRequest(
            $request->requestUri
        );
        $propFindReq->prop = new ezcWebdavBasicPropertyStorage();
        $propFindReq->prop->attach(
            new ezcWebdavLockDiscoveryProperty()
        );
        $propFindReq->prop->attach(
            new ezcWebdavLockInfoProperty()
        );
        ezcWebdavLockTools::cloneRequestHeaders( $request, $propFindReq );
        $propFindReq->setHeader( 'Depth', ezcWebdavRequest::DEPTH_ZERO );
        $propFindReq->validateHeaders();

        $propFindMultistatusRes = $srv->backend->propFind( $propFindReq );

        if ( !( $propFindMultistatusRes instanceof ezcWebdavMultistatusResponse ) )
        {
            return $propFindMultistatusRes;
        }

        $lockDiscoveryProp = null;
        $lockInfoProp = null;

        foreach ( $propFindMultistatusRes->responses as $propFindRes )
        {
            foreach( $propFindRes->responses as $propStatRes )
            {
                if ( $propStatRes->storage->contains( 'lockdiscovery' )
                     && $lockDiscoveryProp === null )
                {
                    $lockDiscoveryProp = $propStatRes->storage->get( 'lockdiscovery' );
                }
                if ( $propStatRes->storage->contains( 'lockinfo', ezcWebdavLockPlugin::XML_NAMESPACE )
                     && $lockInfoProp === null )
                {
                    $lockInfoProp = $propStatRes->storage->get( 'lockinfo', ezcWebdavLockPlugin::XML_NAMESPACE );
                }
                if ( $lockInfoProp !== null && $lockDiscoveryProp !== null )
                {
                    // Found both, finish
                    break 2;
                }
            }
        }

        if ( $lockDiscoveryProp === null && $lockInfoProp === null )
        {
            // Lock was not found (purged?)! Finish successfully.
            return new ezcWebdavResponse( ezcWebdavResponse::STATUS_204 );
        }

        if ( $lockDiscoveryProp === null || $lockInfoProp === null )
        {
            // Inconsistency!
            throw new ezcWebdavInconsistencyException(
                "Properties <lockinfo> and <lockdiscovery> out of sync for path '{$request->requestUri}' with token '$token'."
            );
        }

        $affectedTokenInfo = null;
        foreach ( $lockInfoProp->tokenInfos as $tokenInfo )
        {
            if ( $tokenInfo->token == $token )
            {
                $affectedTokenInfo = $tokenInfo;
            }
        }

        $affectedActiveLock = null;
        foreach ( $lockDiscoveryProp->activeLock as $activeLock )
        {
            // Note the ==, sinde $activeLock->token is an instance of
            // ezcWebdavPotentialUriContent
            if ( $activeLock->token == $token )
            {
                $affectedActiveLock = $activeLock;
                break;
            }
        }

        if ( $affectedTokenInfo === null || $affectedActiveLock === null )
        {
            // Lock not present (purged)! Finish successfully.
            return new ezcWebdavResponse( ezcWebdavResponse::STATUS_204 );
        }

        if ( $affectedTokenInfo->lockBase !== null )
        {
            // Requested resource is not the lock base, recurse
            $newRequest = new ezcWebdavUnlockRequest( $affectedTokenInfo->lockBase );
            ezcWebdavLockTools::cloneRequestHeaders( $request, $newRequest, array( 'If', 'Lock-Token' ) );
            $newRequest->validateHeaders();

            // @TODO Should be protected against infinite recursion
            return $this->receivedRequest(
                $newRequest
            );
        }

        // If lock depth is 0, we issue 1 propfind too much here
        // @TODO: Analyse if clients usually lock 0 or infinity
        $res = $this->performUnlock(
            $request->requestUri,
            $token,
            $affectedActiveLock->depth,
            $authHeader
        );

        if ( $res instanceof ezcWebdavUnlockResponse )
        {
            $srv->auth->releaseLock( $authHeader->username, $token );
        }
        return $res;
    }

    /**
     * Performs unlocking.
     *
     * Performs a PROPFIND request with the $depth of the lock with $token on
     * the given $path (which must be the lock base). All affected resources
     * get the neccessary properties updated to reflect the change. Lock null
     * resources in the lock are removed.
     * 
     * @param string $path 
     * @param string $token 
     * @param int $depth 
     * @return ezcWebdavResponse
     */
    protected function performUnlock( $path, $token, $depth, ezcWebdavAuth $authHeader )
    {
        $backend = ezcWebdavServer::getInstance()->backend;

        // Find alle resources affected by the unlock, including affected properties

        $propFindReq = new ezcWebdavPropFindRequest( $path );
        $propFindReq->prop = new ezcWebdavBasicPropertyStorage();
        $propFindReq->prop->attach( new ezcWebdavLockInfoProperty() );
        $propFindReq->prop->attach( new ezcWebdavLockDiscoveryProperty() );
        $propFindReq->setHeader( 'Depth', $depth );
        $propFindReq->setHeader( 'Authorization', $authHeader );
        $propFindReq->validateHeaders();

        $propFindMultistatusRes = $backend->propFind( $propFindReq );

        // Remove lock information for the lock identified by $token from each affected resource

        foreach ( $propFindMultistatusRes->responses as $propFindRes )
        {
            // Takes properties to be updated
            $changeProps = new ezcWebdavFlaggedPropertyStorage();

            foreach ( $propFindRes->responses as $propStatRes )
            {
                if ( $propStatRes->status === ezcWebdavResponse::STATUS_200 )
                {
                    // Remove affected tokeninfo from lockinfo property

                    if ( $propStatRes->storage->contains( 'lockinfo', ezcWebdavLockPlugin::XML_NAMESPACE ) )
                    {
                        $lockInfoProp = $propStatRes->storage->get( 'lockinfo', ezcWebdavLockPlugin::XML_NAMESPACE );
                        foreach( $lockInfoProp->tokenInfos as $id => $tokenInfo )
                        {
                            if ( $tokenInfo->token === $token )
                            {
                                // Not a null resource

                                $lockInfoProp->tokenInfos->offsetUnset( $id );
                                if ( count( $lockInfoProp->tokenInfos ) === 0 )
                                {
                                    $changeProps->attach(
                                        $lockInfoProp,
                                        ezcWebdavPropPatchRequest::REMOVE
                                    );
                                }
                                else
                                {
                                    // Should not occur now, only with shared locks!
                                    $changeProps->attach(
                                        $lockInfoProp,
                                        ezcWebdavPropPatchRequest::SET
                                    );
                                }

                                break;
                            }

                            if ( $lockInfoProp->null === true && count( $lockInfoProp->tokenInfos ) === 0 )
                            {
                                // Null lock resource, delete when no more locks are active

                                $deleteReq = new ezcWebdavDeleteRequest( $propFindRes->node->path );
                                $deleteReq->validateHeaders();
                                $deleteRes = $backend->delete( $deleteReq );
                                if ( !( $deleteRes instanceof ezcWebdavDeleteResponse ) )
                                {
                                    return $deleteRes;
                                }
                                // Skip over further property assignements and PROPPATCH
                                continue 2;
                            }
                        }
                    }
                    
                    // Remove affected active lock part from lockdiscovery property

                    if ( $propStatRes->storage->contains( 'lockdiscovery' ) )
                    {
                        $lockDiscoveryProp = $propStatRes->storage->get( 'lockdiscovery' );
                        foreach ( $lockDiscoveryProp->activeLock as $id => $activeLock )
                        {
                            if ( $activeLock->token == $token )
                            {
                                $lockDiscoveryProp->activeLock->offsetUnset( $id );

                                $changeProps->attach(
                                    $lockDiscoveryProp,
                                    ezcWebdavPropPatchRequest::SET
                                );
                                break;
                            }
                        }
                    }
                }
            }

            // If changed properties have been assigned (in a normal case,
            // both!), perform the PROPPATCH

            if ( count( $changeProps ) )
            {
                $propPatchReq = new ezcWebdavPropPatchRequest(
                    $propFindRes->node->path
                );
                $propPatchReq->updates = $changeProps;
                $propPatchReq->validateHeaders();

                $propPatchRes = $backend->propPatch( $propPatchReq );

                if ( !( $propPatchRes instanceof ezcWebdavPropPatchResponse ) )
                {
                    throw new ezcWebdavInconsistencyException(
                        "Lock token $token could not be unlocked on resource {$propFindRes->node->path}."
                    );
                }
            }
        }

        return new ezcWebdavUnlockResponse( ezcWebdavResponse::STATUS_204 );
    }
}

?>
